<?php

namespace App\Jobs;

use App\Repositories\Log\LogSyncStateRepository;
use App\Services\Log\LogSyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncApiRequestLogsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function __construct(
        public string $environment,
        public string $triggeredBy = 'schedule',
    ) {}

    public function uniqueId(): string
    {
        return 'sync-api-request-logs-'.$this->environment;
    }

    public function handle(LogSyncService $sync): void
    {
        Log::info('log_sync.job_started', [
            'environment' => $this->environment,
            'triggered_by' => $this->triggeredBy,
        ]);

        $sync->sync($this->environment, dryRun: false);
    }

    public function failed(?Throwable $e): void
    {
        Log::error('log_sync.job_failed', [
            'environment' => $this->environment,
            'error' => $e?->getMessage(),
        ]);

        app(LogSyncStateRepository::class)->markFailed(
            $this->environment,
            $e?->getMessage() ?? 'Queue job failed.'
        );
    }
}
