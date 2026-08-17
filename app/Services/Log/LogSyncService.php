<?php

namespace App\Services\Log;

use App\Repositories\Log\LogSyncStateRepository;
use App\Repositories\Log\MonitoringLogRepository;
use App\Repositories\Log\ProductionLogRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LogSyncService
{
    public function __construct(
        private ProductionLogRepository $production,
        private MonitoringLogRepository $monitoring,
        private LogSyncStateRepository $stateRepo,
        private LogEventResolver $resolver,
        private LogReferenceExtractor $extractor,
    ) {}

    /**
     * @return array{
     *   processed:int,inserted:int,invalid_json:int,unmapped_event:int,
     *   missing_reference:int,dry_run:bool,environment:string
     * }
     */
    public function sync(string $environment, bool $dryRun = false, ?int $batchSize = null): array
    {
        if (! config('armos_log.enabled', true) && ! $dryRun) {
            throw new RuntimeException('Log sync disabled (LOG_SYNC_ENABLED=false).');
        }

        $batchSize = $batchSize ?? (int) config('armos_log.batch_size', 5000);
        $lockKey = (int) config('armos_log.advisory_lock_key', 8142026) + ($environment === 'prod' ? 1 : 2);

        $locked = $this->monitoring->tryAdvisoryLock($lockKey);
        if (! $locked) {
            throw new RuntimeException('Log synchronization is currently running.');
        }

        $stats = [
            'processed' => 0,
            'inserted' => 0,
            'invalid_json' => 0,
            'unmapped_event' => 0,
            'missing_reference' => 0,
            'dry_run' => $dryRun,
            'environment' => $environment,
        ];

        try {
            if (! $dryRun) {
                $this->stateRepo->markRunning($environment);
            }

            Log::info('log_sync.started', [
                'environment' => $environment,
                'dry_run' => $dryRun,
                'batch_size' => $batchSize,
            ]);

            $state = $this->stateRepo->getOrCreate($environment);
            $lastDate = $state->last_synced_created_date;
            $lastId = $state->last_synced_id !== null ? (int) $state->last_synced_id : null;
            $initialFrom = null;
            if ($lastDate === null && config('armos_log.initial_from')) {
                $initialFrom = Carbon::parse((string) config('armos_log.initial_from'));
            }

            while (true) {
                $batch = $this->production->fetchBatch(
                    $environment,
                    $lastDate,
                    $lastId,
                    $batchSize,
                    $initialFrom
                );

                if ($batch === []) {
                    break;
                }

                $rows = [];
                $checkpointDate = null;
                $checkpointId = null;

                foreach ($batch as $row) {
                    $stats['processed']++;
                    $event = isset($row['event']) ? (string) $row['event'] : null;
                    $slug = $this->resolver->resolve($event);
                    if ($slug === 'other') {
                        $stats['unmapped_event']++;
                        Log::info('log_sync.unmapped_event', [
                            'api_request_log_id' => $row['api_request_log_id'] ?? null,
                            'event' => $event,
                        ]);
                    }

                    $searchField = $this->resolver->searchField($slug);
                    $extracted = $this->extractor->extract($row['request'] ?? null, $searchField);
                    if ($extracted['invalid_json']) {
                        $stats['invalid_json']++;
                        Log::warning('log_sync.invalid_json', [
                            'api_request_log_id' => $row['api_request_log_id'] ?? null,
                            'event_slug' => $slug,
                        ]);
                    }
                    if ($searchField !== null && $extracted['value'] === null && ! $extracted['invalid_json']) {
                        $stats['missing_reference']++;
                    }

                    $created = Carbon::parse($row['created_date']);
                    $id = (int) $row['api_request_log_id'];
                    $checkpointDate = $created;
                    $checkpointId = $id;

                    $rows[] = [
                        'api_request_log_id' => $id,
                        'environment' => $environment,
                        'event_slug' => $slug,
                        'reference_value' => $extracted['value'],
                        'created_date' => $created->format('Y-m-d H:i:s.u'),
                        'synced_at' => now()->format('Y-m-d H:i:s'),
                    ];
                }

                if (! $dryRun) {
                    $this->monitoring->upsertBatch($rows);
                    $stats['inserted'] += count($rows);
                    if ($checkpointDate !== null && $checkpointId !== null) {
                        $this->stateRepo->advanceCheckpoint($environment, $checkpointDate, $checkpointId);
                        $lastDate = $checkpointDate;
                        $lastId = $checkpointId;
                    }
                } else {
                    $stats['inserted'] += count($rows);
                    $lastDate = $checkpointDate;
                    $lastId = $checkpointId;
                }

                Log::info('log_sync.batch_processed', [
                    'environment' => $environment,
                    'batch_count' => count($batch),
                    'dry_run' => $dryRun,
                ]);

                if (count($batch) < $batchSize) {
                    break;
                }
            }

            if (! $dryRun) {
                $this->stateRepo->markCompleted($environment, $stats['inserted']);
            }

            Log::info('log_sync.finished', $stats);

            return $stats;
        } catch (\Throwable $e) {
            if (! $dryRun) {
                $this->stateRepo->markFailed($environment, $e->getMessage());
            }
            Log::error('log_sync.failed', [
                'environment' => $environment,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            $this->monitoring->advisoryUnlock($lockKey);
        }
    }

    /**
     * Validate cooldown + not running, then return true if dispatch allowed.
     *
     * @return array{allowed:bool,message:string,status:string,next_sync_at:?string}
     */
    public function canStartManualSync(string $environment): array
    {
        $state = $this->stateRepo->getOrCreate($environment);

        if ($state->status === 'running') {
            return [
                'allowed' => false,
                'message' => 'Log synchronization is currently running.',
                'status' => 'running',
                'next_sync_at' => optional($this->stateRepo->nextManualSyncAt($state))?->toIso8601String(),
            ];
        }

        if ($this->stateRepo->isInCooldown($state)) {
            $next = $this->stateRepo->nextManualSyncAt($state);

            return [
                'allowed' => false,
                'message' => 'Log synchronization is still in cooldown.',
                'status' => $state->status,
                'next_sync_at' => optional($next)?->toIso8601String(),
            ];
        }

        return [
            'allowed' => true,
            'message' => 'OK',
            'status' => $state->status,
            'next_sync_at' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function statusPayload(string $environment): array
    {
        $state = $this->stateRepo->getOrCreate($environment);
        $next = $this->stateRepo->nextManualSyncAt($state);

        return [
            'environment' => $environment,
            'status' => $state->status,
            'last_sync_started_at' => optional($state->last_sync_started_at)?->toDateTimeString(),
            'last_sync_finished_at' => optional($state->last_sync_finished_at)?->toDateTimeString(),
            'last_sync_records' => (int) $state->last_sync_records,
            'last_synced_created_date' => optional($state->last_synced_created_date)?->toDateTimeString(),
            'last_synced_id' => $state->last_synced_id,
            'next_manual_sync_at' => optional($next)?->toDateTimeString(),
            'cooldown_active' => $this->stateRepo->isInCooldown($state) || $state->status === 'running',
            'last_error' => $state->last_error,
        ];
    }
}
