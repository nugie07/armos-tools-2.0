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
