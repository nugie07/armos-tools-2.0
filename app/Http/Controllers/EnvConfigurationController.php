<?php

namespace App\Http\Controllers;

use App\Models\ToolsEnvSetting;
use App\Services\Tms\TmsDatabase;
use Illuminate\Http\Request;
use Throwable;

class EnvConfigurationController extends Controller
{
    public function index()
    {
        $definitions = ToolsEnvSetting::definitions();
        $values = ToolsEnvSetting::query()->pluck('value', 'key')->all();

        // Prefill defaults from .env if DB empty
        foreach ($definitions as $group => $keys) {
            foreach ($keys as $key) {
                if (! array_key_exists($key, $values) || $values[$key] === null || $values[$key] === '') {
                    $values[$key] = env($key, $key === 'WH_TYPE' ? '9' : '');
                }
            }
        }

        // Migrate legacy per-env Convert & Send keys → General (jika masih ada di DB)
        $legacyToGeneral = [
            'AUTH_URL' => ['AUTH_URL_PROD', 'AUTH_URL_PREPROD'],
            'FEED_ORDER_URL' => ['FEED_ORDER_URL_PROD', 'FEED_ORDER_URL_PREPROD'],
            'SEND_ORDER_USERNAME' => ['SEND_ORDER_USERNAME_PROD', 'SEND_ORDER_USERNAME_PREPROD'],
            'SEND_ORDER_PASSWORD' => ['SEND_ORDER_PASSWORD_PROD', 'SEND_ORDER_PASSWORD_PREPROD'],
        ];
        foreach ($legacyToGeneral as $generalKey => $legacyKeys) {
            if (($values[$generalKey] ?? '') !== '') {
                continue;
            }
            foreach ($legacyKeys as $legacyKey) {
                $fromDb = ToolsEnvSetting::getValue($legacyKey);
                if ($fromDb !== null && $fromDb !== '') {
                    $values[$generalKey] = $fromDb;
                    break;
                }
            }
        }

        return view('admin.env.index', compact('definitions', 'values'));
    }

    public function update(Request $request)
    {
        $definitions = ToolsEnvSetting::definitions();
        $allowed = collect($definitions)->flatten()->all();

        $payload = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string'],
            'active_tab' => ['nullable', 'in:production,preprod,general'],
        ]);

        foreach ($payload['settings'] as $key => $value) {
            if (! in_array($key, $allowed, true)) {
                continue;
            }

            $group = 'general';
            foreach ($definitions as $g => $keys) {
                if (in_array($key, $keys, true)) {
                    $group = $g;
                    break;
                }
            }

            ToolsEnvSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }

        ToolsEnvSetting::forgetCache();

        return redirect()
            ->route('env-config.index', ['tab' => $payload['active_tab'] ?? 'production'])
            ->with('success', 'Env configuration berhasil disimpan.');
    }

    public function testConnection(Request $request)
    {
        $data = $request->validate([
            'target' => ['required', 'in:production,preprod'],
            'host' => ['nullable', 'string'],
            'port' => ['nullable', 'string'],
            'database' => ['nullable', 'string'],
            'username' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        $prefix = $data['target'] === 'production' ? 'DATABASE_PROD' : 'DATABASE_PREPROD';
        $label = $data['target'] === 'production' ? 'Production' : 'Pre Production';

        $config = [
            'host' => $data['host'] ?: ToolsEnvSetting::getValue("{$prefix}_HOST") ?: env("{$prefix}_HOST"),
            'port' => $data['port'] ?: ToolsEnvSetting::getValue("{$prefix}_PORT") ?: env("{$prefix}_PORT", '5432'),
            'database' => $data['database'] ?: ToolsEnvSetting::getValue("{$prefix}_NAME") ?: env("{$prefix}_NAME"),
            'username' => $data['username'] ?: ToolsEnvSetting::getValue("{$prefix}_USERNAME") ?: env("{$prefix}_USERNAME"),
            'password' => array_key_exists('password', $data) && $data['password'] !== null
                ? $data['password']
                : (ToolsEnvSetting::getValue("{$prefix}_PASS") ?: env("{$prefix}_PASS", '')),
        ];

        try {
            TmsDatabase::testConnection($config);

            return response()->json([
                'status' => 200,
                'message' => "Koneksi {$label} berhasil.",
                'data' => [
                    'target' => $data['target'],
                    'host' => $config['host'],
                    'database' => $config['database'],
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
                'data' => [
                    'target' => $data['target'],
                    'host' => $config['host'],
                    'database' => $config['database'],
                ],
            ], 400);
        }
    }
}
