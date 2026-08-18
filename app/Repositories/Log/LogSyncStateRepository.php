<?php

namespace App\Repositories\Log;

use App\Models\LogSyncState;
use Carbon\CarbonInterface;

class LogSyncStateRepository
{
    public function getOrCreate(string $environment): LogSyncState
    {
        return LogSyncState::query()->firstOrCreate(
            ['environment' => $environment],
            [
                'status' => 'idle',
                'last_sync_records' => 0,
            ]
        );
    }

    public function markRunning(string $environment): LogSyncState
    {
        $state = $this->getOrCreate($environment);
        $state->fill([
            'status' => 'running',
            'last_sync_started_at' => now(),
            'last_error' => null,
        ]);
        $state->save();

        return $state->fresh();
    }

    public function markCompleted(string $environment, int $records): LogSyncState
    {
        $state = $this->getOrCreate($environment);
        $state->fill([
            'status' => 'completed',
            'last_sync_finished_at' => now(),
            'last_sync_records' => $records,
            'last_error' => null,
        ]);
        $state->save();

        return $state->fresh();
    }

    public function updateProgress(string $environment, int $records): void
    {
        $state = $this->getOrCreate($environment);
        $state->last_sync_records = $records;
        $state->save();
    }

    public function isStaleRunning(LogSyncState $state): bool
    {
        if ($state->status !== 'running' || $state->last_sync_started_at === null) {
            return false;
        }

        $minutes = max(5, (int) config('armos_log.stale_running_minutes', 70));

        return $state->last_sync_started_at->lte(now()->subMinutes($minutes));
    }

    public function markFailed(string $environment, string $error): LogSyncState
    {
        $state = $this->getOrCreate($environment);
        $state->fill([
            'status' => 'failed',
            'last_sync_finished_at' => now(),
            'last_error' => mb_substr($error, 0, 2000),
        ]);
        $state->save();

        return $state->fresh();
    }

    public function advanceCheckpoint(
        string $environment,
        CarbonInterface $createdDate,
        int $id,
    ): void {
        $state = $this->getOrCreate($environment);
        $state->fill([
            'last_synced_created_date' => $createdDate,
            'last_synced_id' => $id,
        ]);
        $state->save();
    }

    public function nextManualSyncAt(LogSyncState $state): ?\Carbon\CarbonInterface
    {
        if ($state->last_sync_started_at === null) {
            return null;
        }

        $minutes = (int) config('armos_log.manual_cooldown_minutes', 60);

        return $state->last_sync_started_at->copy()->addMinutes($minutes);
    }

    public function isInCooldown(LogSyncState $state): bool
    {
        $next = $this->nextManualSyncAt($state);
        if ($next === null) {
            return false;
        }

        return $next->isFuture();
    }
}
