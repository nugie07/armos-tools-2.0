<?php

namespace App\Services\Sync;

use App\Services\Tms\TmsDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncManagerService
{
    /**
     * Sync memakai DB Production / Pre Production dari session navbar
     * (sama seperti menu lain) — tidak lagi DB_A / DB_B terpisah.
     */
    protected function connection(): string
    {
        return TmsDatabase::main();
    }

    public function ensureLogTable(): void
    {
        $conn = $this->connection();
        DB::connection($conn)->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS tms_sync_log (
    id SERIAL PRIMARY KEY,
    sync_type VARCHAR(50) NOT NULL,
    start_time TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP WITH TIME ZONE,
    status VARCHAR(20) NOT NULL,
    records_processed INTEGER DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
)
SQL);
    }

    public function run(string $syncType, ?string $dateFrom = null, ?string $dateTo = null): string
    {
        $this->ensureLogTable();
        $conn = $this->connection();

        $rows = TmsDatabase::select(
            $conn,
            "INSERT INTO tms_sync_log (sync_type, status, start_time) VALUES (?, 'RUNNING', CURRENT_TIMESTAMP) RETURNING id",
            [$syncType]
        );
        $syncId = (int) ($rows[0]['id'] ?? 0);
        $jobId = (string) Str::uuid();

        Cache::put('sync_job_'.$jobId, [
            'job_id' => $jobId,
            'sync_id' => $syncId,
            'sync_type' => $syncType,
            'status' => 'RUNNING',
            'state' => 'in-progress',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'progress' => 0,
        ], now()->addHours(6));

        try {
            $processed = $this->runInline($syncType, $dateFrom, $dateTo);
            TmsDatabase::affectingStatement($conn, <<<'SQL'
UPDATE tms_sync_log
SET end_time = CURRENT_TIMESTAMP,
    status = ?,
    records_processed = ?,
    error_message = NULL
WHERE id = ?
SQL, ['SUCCESS', $processed, $syncId]);

            Cache::put('sync_job_'.$jobId, [
                'job_id' => $jobId,
                'sync_id' => $syncId,
                'sync_type' => $syncType,
                'status' => 'SUCCESS',
                'state' => 'done',
                'progress' => 100,
                'records_processed' => $processed,
            ], now()->addHours(6));
        } catch (\Throwable $e) {
            TmsDatabase::affectingStatement($conn, <<<'SQL'
UPDATE tms_sync_log
SET end_time = CURRENT_TIMESTAMP,
    status = ?,
    records_processed = 0,
    error_message = ?
WHERE id = ?
SQL, ['FAILED', $e->getMessage(), $syncId]);

            Cache::put('sync_job_'.$jobId, [
                'job_id' => $jobId,
                'sync_id' => $syncId,
                'sync_type' => $syncType,
                'status' => 'FAILED',
                'state' => 'fail',
                'progress' => 100,
                'error' => $e->getMessage(),
            ], now()->addHours(6));
        }

        return $jobId;
    }

    protected function runInline(string $syncType, ?string $dateFrom, ?string $dateTo): int
    {
        TmsDatabase::tryConnect($this->connection());

        return 0;
    }

    public function job(string $jobId): array
    {
        $data = Cache::get('sync_job_'.$jobId);
        if (! $data) {
            throw new \RuntimeException('Job tidak ditemukan: '.$jobId);
        }

        return $data;
    }

    public function status(?string $syncType = null, int $page = 1, int $limit = 20): array
    {
        $this->ensureLogTable();
        $conn = $this->connection();
        $offset = max(0, ($page - 1) * $limit);

        if ($syncType) {
            $rows = TmsDatabase::select($conn, <<<'SQL'
SELECT sync_type, start_time, end_time, status, records_processed, error_message
FROM tms_sync_log
WHERE sync_type = ?
ORDER BY start_time DESC
LIMIT ? OFFSET ?
SQL, [$syncType, $limit, $offset]);
            $total = (int) (TmsDatabase::select($conn, 'SELECT COUNT(1) AS total FROM tms_sync_log WHERE sync_type = ?', [$syncType])[0]['total'] ?? 0);
        } else {
            $rows = TmsDatabase::select($conn, <<<'SQL'
SELECT sync_type, start_time, end_time, status, records_processed, error_message
FROM tms_sync_log
ORDER BY start_time DESC
LIMIT ? OFFSET ?
SQL, [$limit, $offset]);
            $total = (int) (TmsDatabase::select($conn, 'SELECT COUNT(1) AS total FROM tms_sync_log')[0]['total'] ?? 0);
        }

        $ok = (int) (TmsDatabase::select($conn, "SELECT COUNT(1) AS c FROM tms_sync_log WHERE status = 'SUCCESS'")[0]['c'] ?? 0);
        $fail = (int) (TmsDatabase::select($conn, "SELECT COUNT(1) AS c FROM tms_sync_log WHERE status = 'FAILED'")[0]['c'] ?? 0);
        $last = TmsDatabase::select($conn, 'SELECT start_time FROM tms_sync_log ORDER BY start_time DESC LIMIT 1');

        $pages = max(1, (int) ceil($total / max(1, $limit)));

        return [
            'sync_history' => $rows,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'total' => $total,
            'stats' => [
                'total_syncs' => $total,
                'successful_syncs' => $ok,
                'failed_syncs' => $fail,
                'last_sync' => $last[0]['start_time'] ?? null,
            ],
        ];
    }
}
