<?php

namespace Tests\Unit;

use App\Models\LogSyncState;
use App\Repositories\Log\LogSyncStateRepository;
use App\Services\Log\LogEventResolver;
use App\Services\Log\LogReferenceExtractor;
use App\Services\Log\LogSyncService;
use App\Repositories\Log\MonitoringLogRepository;
use App\Repositories\Log\ProductionLogRepository;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

class LogSyncCooldownTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_manual_sync_rejected_during_cooldown(): void
    {
        $state = new LogSyncState([
            'environment' => 'prod',
            'status' => 'completed',
            'last_sync_started_at' => Carbon::now()->subMinutes(10),
            'last_sync_records' => 10,
        ]);

        $stateRepo = Mockery::mock(LogSyncStateRepository::class);
        $stateRepo->shouldReceive('getOrCreate')->andReturn($state);
        $stateRepo->shouldReceive('isStaleRunning')->andReturn(false);
        $stateRepo->shouldReceive('isInCooldown')->andReturn(true);
        $stateRepo->shouldReceive('nextManualSyncAt')->andReturn(Carbon::now()->addMinutes(50));

        $service = new LogSyncService(
            Mockery::mock(ProductionLogRepository::class),
            Mockery::mock(MonitoringLogRepository::class),
            $stateRepo,
            new LogEventResolver,
            new LogReferenceExtractor,
        );

        $gate = $service->canStartManualSync('prod');
        $this->assertFalse($gate['allowed']);
        $this->assertSame('Log synchronization is still in cooldown.', $gate['message']);
    }

    public function test_manual_sync_rejected_when_running(): void
    {
        $state = new LogSyncState([
            'environment' => 'prod',
            'status' => 'running',
            'last_sync_started_at' => Carbon::now(),
        ]);

        $stateRepo = Mockery::mock(LogSyncStateRepository::class);
        $stateRepo->shouldReceive('getOrCreate')->andReturn($state);
        $stateRepo->shouldReceive('isStaleRunning')->andReturn(false);
        $stateRepo->shouldReceive('nextManualSyncAt')->andReturn(Carbon::now()->addHour());

        $service = new LogSyncService(
            Mockery::mock(ProductionLogRepository::class),
            Mockery::mock(MonitoringLogRepository::class),
            $stateRepo,
            new LogEventResolver,
            new LogReferenceExtractor,
        );

        $gate = $service->canStartManualSync('prod');
        $this->assertFalse($gate['allowed']);
        $this->assertSame('running', $gate['status']);
    }

    public function test_manual_sync_allowed_when_idle_and_no_cooldown(): void
    {
        $state = new LogSyncState([
            'environment' => 'prod',
            'status' => 'idle',
            'last_sync_started_at' => null,
        ]);

        $stateRepo = Mockery::mock(LogSyncStateRepository::class);
        $stateRepo->shouldReceive('getOrCreate')->andReturn($state);
        $stateRepo->shouldReceive('isStaleRunning')->andReturn(false);
        $stateRepo->shouldReceive('isInCooldown')->andReturn(false);

        $service = new LogSyncService(
            Mockery::mock(ProductionLogRepository::class),
            Mockery::mock(MonitoringLogRepository::class),
            $stateRepo,
            new LogEventResolver,
            new LogReferenceExtractor,
        );

        $gate = $service->canStartManualSync('prod');
        $this->assertTrue($gate['allowed']);
    }

    public function test_lookback_defaults_to_fourteen_days(): void
    {
        Carbon::setTestNow('2026-08-18 21:00:00');
        config(['armos_log.lookback_days' => 14, 'armos_log.initial_from' => null]);

        $service = new LogSyncService(
            Mockery::mock(ProductionLogRepository::class),
            Mockery::mock(MonitoringLogRepository::class),
            Mockery::mock(LogSyncStateRepository::class),
            new LogEventResolver,
            new LogReferenceExtractor,
        );

        $this->assertSame('2026-08-04 00:00:00', $service->lookbackFrom()->toDateTimeString());
        Carbon::setTestNow();
    }

    public function test_stale_running_is_reset_and_manual_sync_allowed_if_no_cooldown(): void
    {
        $running = new LogSyncState([
            'environment' => 'prod',
            'status' => 'running',
            'last_sync_started_at' => Carbon::now()->subHours(2),
        ]);
        $failed = new LogSyncState([
            'environment' => 'prod',
            'status' => 'failed',
            'last_sync_started_at' => Carbon::now()->subHours(2),
        ]);

        $stateRepo = Mockery::mock(LogSyncStateRepository::class);
        $stateRepo->shouldReceive('getOrCreate')->andReturn($running, $failed);
        $stateRepo->shouldReceive('isStaleRunning')->with($running)->andReturn(true);
        $stateRepo->shouldReceive('markFailed')->once();
        $stateRepo->shouldReceive('isInCooldown')->andReturn(false);

        $service = new LogSyncService(
            Mockery::mock(ProductionLogRepository::class),
            Mockery::mock(MonitoringLogRepository::class),
            $stateRepo,
            new LogEventResolver,
            new LogReferenceExtractor,
        );

        $gate = $service->canStartManualSync('prod');
        $this->assertTrue($gate['allowed']);
    }
}
