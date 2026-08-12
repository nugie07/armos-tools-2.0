<?php

namespace App\Services\ConvertSend;

use App\Services\Tms\TmsDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ConvertSendService
{
    /**
     * Upload Excel → convert → auth (AUTH_URL) → feed order (FEED_ORDER_URL).
     * Kredensial dari General Config di table Env Configuration (satu set untuk PROD).
     * Tidak ada hardcode username/password.
     */
    public function handle(UploadedFile $file): array
    {
        $steps = [];
        $stored = $file->storeAs('convert_send', now()->format('Ymd_His').'_'.$file->getClientOriginalName());
        $steps[] = ['status' => 200, 'message' => 'File tersimpan: '.$stored];

        $converted = [
            'source' => $file->getClientOriginalName(),
            'stored_path' => $stored,
            'note' => 'Konversi Excel penuh (order_data/order_detail) akan menyusul; struktur response siap.',
            'orders' => [],
        ];
        $steps[] = ['status' => 200, 'message' => 'Convert JSON (struktur dasar)'];

        try {
            $cfg = $this->resolveAuthConfig();
        } catch (RuntimeException $e) {
            $steps[] = ['status' => 400, 'message' => $e->getMessage()];

            return [
                'steps' => $steps,
                'converted_json' => $converted,
            ];
        }

        try {
            $token = $this->loginGetToken($cfg['auth_url'], $cfg['username'], $cfg['password']);
            $steps[] = ['status' => 200, 'message' => 'Auth token berhasil'];
        } catch (\Throwable $e) {
            $steps[] = ['status' => 500, 'message' => 'Auth gagal: '.$e->getMessage()];

            return [
                'steps' => $steps,
                'converted_json' => $converted,
            ];
        }

        $steps[] = ['status' => 200, 'message' => 'Feed order dilewati (belum ada order hasil convert). Target: '.$cfg['feed_url']];

        return [
            'steps' => $steps,
            'converted_json' => $converted,
            'token_acquired' => true,
        ];
    }

    /**
     * @return array{auth_url:string,feed_url:string,username:string,password:string}
     */
    protected function resolveAuthConfig(): array
    {
        return [
            'auth_url' => $this->requiredSetting('AUTH_URL'),
            'feed_url' => $this->requiredSetting('FEED_ORDER_URL'),
            'username' => $this->requiredSetting('SEND_ORDER_USERNAME'),
            'password' => $this->requiredSetting('SEND_ORDER_PASSWORD'),
        ];
    }

    protected function requiredSetting(string $key): string
    {
        $value = TmsDatabase::setting($key);
        if ($value === null || $value === '') {
            throw new RuntimeException(
                "{$key} belum diisi di Env Configuration (tab General Config)."
            );
        }

        return $value;
    }

    protected function loginGetToken(string $authUrl, string $username, string $password): string
    {
        $response = Http::timeout(30)->post($authUrl, [
            'username' => $username,
            'password' => $password,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('HTTP '.$response->status().' dari AUTH_URL');
        }

        $json = $response->json();
        if (is_array($json) && array_key_exists('success', $json) && ! $json['success']) {
            throw new RuntimeException('Login gagal: '.json_encode($json));
        }

        $token = $json['token'] ?? $json['access_token'] ?? $json['data']['token'] ?? null;
        if (! $token) {
            throw new RuntimeException('Token tidak ditemukan di response AUTH_URL');
        }

        return (string) $token;
    }
}
