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
     * Incremental read from TMS sys_api_request_log, never older than $lookbackFrom.
     *
     * @return list<array<string, mixed>>
     */
    public function fetchBatch(
        string $environment,
        ?CarbonInterface $lastSyncedCreatedDate,
        ?int $lastSyncedId,
        int $batchSize,
        CarbonInterface $lookbackFrom,
    ): array {
        $conn = $this->connectionName($environment);
        $table = config('armos_log.production_table', 'sys_api_request_log');

        $bindings = [$lookbackFrom->format('Y-m-d H:i:s')];
        $where = 'created_date >= ?';

        if ($lastSyncedCreatedDate !== null && $lastSyncedCreatedDate->gte($lookbackFrom)) {
            $where .= ' AND (created_date > ? OR (created_date = ? AND api_request_log_id > ?))';
            $ts = $lastSyncedCreatedDate->format('Y-m-d H:i:s.u');
            $bindings[] = $ts;
            $bindings[] = $ts;
            $bindings[] = (int) ($lastSyncedId ?? 0);
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
