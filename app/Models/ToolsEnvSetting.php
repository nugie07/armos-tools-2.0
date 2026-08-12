<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ToolsEnvSetting extends Model
{
    protected $table = 'tools_env_settings';

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $all = static::allCached();

        return $all[$key] ?? $default;
    }

    /**
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        return Cache::remember('tools_env_settings', 60, function () {
            return static::query()->pluck('value', 'key')->all();
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget('tools_env_settings');
    }

    /**
     * @return array<string, list<string>>
     */
    public static function definitions(): array
    {
        return [
            'production' => [
                'DATABASE_PROD_HOST',
                'DATABASE_PROD_PORT',
                'DATABASE_PROD_NAME',
                'DATABASE_PROD_USERNAME',
                'DATABASE_PROD_PASS',
            ],
            'preprod' => [
                'DATABASE_PREPROD_HOST',
                'DATABASE_PREPROD_PORT',
                'DATABASE_PREPROD_NAME',
                'DATABASE_PREPROD_USERNAME',
                'DATABASE_PREPROD_PASS',
            ],
            'general' => [
                // Convert & Send (menu 6) — koneksi auth/feed ke PROD
                'AUTH_URL',
                'FEED_ORDER_URL',
                'SEND_ORDER_USERNAME',
                'SEND_ORDER_PASSWORD',
                'WMS_PROD_URL',
                'WMS_API_KEY',
                'WMS_SECRET',
                'WMS_LIST_INV',
                'WH_TYPE',
                'SUPABASE_URL',
                'SUPABASE_KEY',
            ],
        ];
    }
}
