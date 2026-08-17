<?php

namespace App\Console\Commands;

use App\Jobs\SyncApiRequestLogsJob;
use App\Services\Log\LogSyncService;
use Illuminate\Console\Command;

class SyncApiRequestLogs extends Command
{
    protected $signature = 'logs:sync
        {--env= : prod|preprod (default: both for schedule, or required for manual)}
        {--batch-size= : Override LOG_SYNC_BATCH_SIZE}
        {--from= : Initial lower bound Y-m-d when checkpoint empty}
        {--dry-run : Resolve/extract only, do not write monitoring}
        {--queue : Dispatch job instead of running inline}';

    protected $description = 'Incremental sync sys_api_request_log → monitoring.api_request_log_viewer';

    public function handle(LogSyncService $sync): int
    {
        $envOpt = $this->option('env');
        $environments = $envOpt
            ? [$this->normalizeEnv((string) $envOpt)]
            : ['prod', 'preprod'];

        if ($from = $this->option('from')) {
            config(['armos_log.initial_from' => $from]);
        }

        $batchSize = $this->option('batch-size') !== null
            ? (int) $this->option('batch-size')
            : null;
        $dryRun = (bool) $this->option('dry-run');
        $useQueue = (bool) $this->option('queue');

        foreach ($environments as $environment) {
            $this->info("Sync environment={$environment}".($dryRun ? ' (dry-run)' : ''));

            if ($useQueue && ! $dryRun) {
                SyncApiRequestLogsJob::dispatch($environment, 'artisan');
                $this->info("Dispatched SyncApiRequestLogsJob for {$environment}");
                continue;
            }

            try {
                $stats = $sync->sync($environment, $dryRun, $batchSize);
                $this->table(
                    ['metric', 'value'],
                    collect($stats)->map(fn ($v, $k) => [$k, is_bool($v) ? ($v ? 'true' : 'false') : $v])->values()->all()
                );
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    protected function normalizeEnv(string $env): string
    {
        $normalized = strtolower($env);
        if (in_array($normalized, ['production', 'prod'], true)) {
            return 'prod';
        }
        if ($normalized === 'preprod') {
            return 'preprod';
        }

        throw new \InvalidArgumentException("Invalid --env={$env}. Use prod or preprod.");
    }
}
