<?php

use App\Jobs\SyncApiRequestLogsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$hours = max(1, (int) config('armos_log.schedule_hours', 3));

Schedule::job(new SyncApiRequestLogsJob('prod', 'schedule'))
    ->cron(sprintf('0 */%d * * *', $hours))
    ->withoutOverlapping()
    ->when(fn () => (bool) config('armos_log.enabled', true));

Schedule::job(new SyncApiRequestLogsJob('preprod', 'schedule'))
    ->cron(sprintf('5 */%d * * *', $hours))
    ->withoutOverlapping()
    ->when(fn () => (bool) config('armos_log.enabled', true));
