<?php

namespace App\Jobs;

use App\Services\Log\LogSyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncApiRequestLogsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 7200;

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
}
