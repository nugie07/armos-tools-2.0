<?php

namespace App\Repositories\Log;

use App\Services\Tms\TmsDatabase;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ProductionLogRepository
{
    public function connectionName(string $environment): string
    {
        return TmsDatabase::byEnv($environment);
    }

    /**
     * Incremental read from TMS sys_api_request_log.
     *
     * @return list<array<string, mixed>>
     */
    public function fetchBatch(
        string $environment,
        ?CarbonInterface $lastSyncedCreatedDate,
        ?int $lastSyncedId,
        int $batchSize,
        ?CarbonInterface $initialFrom = null,
    ): array {
        $conn = $this->connectionName($environment);
        $table = config('armos_log.production_table', 'sys_api_request_log');

        $bindings = [];
        $where = '1=1';

        if ($lastSyncedCreatedDate !== null) {
            $where = '(created_date > ? OR (created_date = ? AND api_request_log_id > ?))';
            $ts = $lastSyncedCreatedDate->format('Y-m-d H:i:s.u');
            $bindings[] = $ts;
            $bindings[] = $ts;
            $bindings[] = (int) ($lastSyncedId ?? 0);
        } elseif ($initialFrom !== null) {
            $where = 'created_date >= ?';
            $bindings[] = $initialFrom->format('Y-m-d H:i:s');
        }

        $bindings[] = $batchSize;

        $sql = "SELECT api_request_log_id, event, request, created_date
FROM {$table}
WHERE {$where}
ORDER BY created_date ASC, api_request_log_id ASC
LIMIT ?";

        return TmsDatabase::select($conn, $sql, $bindings);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $environment, int $id): ?array
    {
        $conn = $this->connectionName($environment);
        $table = config('armos_log.production_table', 'sys_api_request_log');

        $rows = TmsDatabase::select(
            $conn,
            "SELECT api_request_log_id, event, request, response, created_date
FROM {$table}
WHERE api_request_log_id = ?
LIMIT 1",
            [$id]
        );

        return $rows[0] ?? null;
    }

    /**
     * PostgreSQL session-level advisory lock (same connection).
     */
    public function tryAdvisoryLock(string $environment, int $key): bool
    {
        $conn = $this->connectionName($environment);
        $row = DB::connection($conn)->selectOne('SELECT pg_try_advisory_lock(?) AS locked', [$key]);

        return (bool) ($row->locked ?? false);
    }

    public function advisoryUnlock(string $environment, int $key): void
    {
        $conn = $this->connectionName($environment);
        DB::connection($conn)->select('SELECT pg_advisory_unlock(?)', [$key]);
    }
}
