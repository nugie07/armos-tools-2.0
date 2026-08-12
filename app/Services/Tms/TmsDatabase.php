<?php

namespace App\Services\Tms;

use App\Models\ToolsEnvSetting;
use App\Support\ArmosEnvironment;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class TmsDatabase
{
    /**
     * Resolve config: tools_env_settings (table) first, then .env fallback.
     */
    public static function setting(string $key, ?string $default = null): ?string
    {
        $fromDb = ToolsEnvSetting::getValue($key);
        if ($fromDb !== null && $fromDb !== '') {
            return $fromDb;
        }

        $fromEnv = env($key);
        if ($fromEnv !== null && $fromEnv !== '') {
            return (string) $fromEnv;
        }

        return $default;
    }

    /**
     * TMS connection mengikuti session navbar (Production / Pre Production).
     */
    public static function main(): string
    {
        return self::byEnv(ArmosEnvironment::apiEnv());
    }

    /**
     * PREPROD / PROD connection dari table Env Configuration.
     * Accepts: preprod|prod|production
     */
    public static function byEnv(string $env): string
    {
        $normalized = strtolower($env);
        if (in_array($normalized, ['production', 'prod'], true)) {
            $normalized = 'prod';
        } elseif ($normalized === 'preprod') {
            $normalized = 'preprod';
        } else {
            throw new RuntimeException("Environment harus 'preprod' atau 'prod', mendapat: {$env}");
        }

        $prefix = $normalized === 'preprod' ? 'DATABASE_PREPROD' : 'DATABASE_PROD';
        $name = $normalized === 'preprod' ? 'tms_preprod' : 'tms_prod';

        $config = [
            'host' => self::setting("{$prefix}_HOST"),
            'port' => self::setting("{$prefix}_PORT", '5432'),
            'database' => self::setting("{$prefix}_NAME"),
            'username' => self::setting("{$prefix}_USERNAME"),
            'password' => self::setting("{$prefix}_PASS", ''),
        ];

        $missing = [];
        foreach (['host', 'database', 'username'] as $field) {
            if (empty($config[$field])) {
                $missing[] = "{$prefix}_".strtoupper($field === 'database' ? 'NAME' : ($field === 'username' ? 'USERNAME' : 'HOST'));
            }
        }
        if ($missing) {
            throw new RuntimeException(
                'Konfigurasi database '.$normalized.' tidak lengkap: '.implode(', ', $missing)
                .' — isi tab '.($normalized === 'preprod' ? 'Pre Production' : 'Production').' di Env Configuration.'
            );
        }

        return self::register($name, $config);
    }

    /**
     * Test koneksi langsung (tanpa register permanent) untuk Env Configuration UI.
     *
     * @param  array{host:?string,port:?string,database:?string,username:?string,password:?string}  $config
     */
    public static function testConnection(array $config): void
    {
        if (empty($config['host']) || empty($config['database']) || empty($config['username'])) {
            throw new RuntimeException('Host, database, dan username wajib diisi.');
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $config['host'],
            $config['port'] ?: '5432',
            $config['database']
        );

        try {
            $pdo = new \PDO($dsn, $config['username'], $config['password'] ?? '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            $pdo->query('SELECT 1');
            $pdo = null;
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal koneksi: '.$e->getMessage(), 0, $e);
        }
    }

    public static function whType(): int
    {
        return (int) self::setting('WH_TYPE', '9');
    }

    /**
     * @param  array{host:?string,port:?string,database:?string,username:?string,password:?string}  $config
     */
    protected static function register(string $name, array $config): string
    {
        if (empty($config['host']) || empty($config['database']) || empty($config['username'])) {
            throw new RuntimeException("Koneksi TMS '{$name}' tidak lengkap. Lengkapi Env Configuration (table).");
        }

        config([
            "database.connections.{$name}" => [
                'driver' => 'pgsql',
                'host' => $config['host'],
                'port' => $config['port'] ?: '5432',
                'database' => $config['database'],
                'username' => $config['username'],
                'password' => $config['password'] ?? '',
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ],
        ]);

        DB::purge($name);

        return $name;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function select(string $connection, string $sql, array $bindings = []): array
    {
        return array_map(
            fn ($row) => (array) $row,
            DB::connection($connection)->select($sql, $bindings)
        );
    }

    public static function affectingStatement(string $connection, string $sql, array $bindings = []): int
    {
        return DB::connection($connection)->affectingStatement($sql, $bindings);
    }

    public static function transaction(string $connection, callable $callback): mixed
    {
        return DB::connection($connection)->transaction($callback);
    }

    public static function tryConnect(string $connection): void
    {
        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal koneksi database TMS: '.$e->getMessage(), 0, $e);
        }
    }
}
