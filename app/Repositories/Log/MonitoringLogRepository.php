<?php

namespace App\Repositories\Log;

use App\Models\ApiRequestLogViewer;
use Illuminate\Support\Facades\DB;

class MonitoringLogRepository
{
    /**
     * @param  list<array{
     *   api_request_log_id:int|string,
     *   environment:string,
     *   event_slug:string,
     *   reference_value:?string,
     *   created_date:mixed,
     *   synced_at:mixed
     * }>  $rows
     */
    public function upsertBatch(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        return ApiRequestLogViewer::query()->upsert(
            $rows,
            ['environment', 'api_request_log_id'],
            ['event_slug', 'reference_value', 'created_date', 'synced_at']
        );
    }

    /**
     * Offset pagination on monitoring index.
     * Date filters compare the calendar date of created_date (inclusive).
     *
     * @return array{
     *   data:list<array<string,mixed>>,
     *   current_page:int,
     *   last_page:int,
     *   per_page:int,
     *   total:int
     * }
     */
    public function search(
        string $environment,
        string $eventSlug,
        ?string $referenceValue,
        ?string $dateFrom,
        ?string $dateTo,
        int $page,
        int $perPage,
    ): array {
        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);

        $query = ApiRequestLogViewer::query()
            ->where('environment', $environment)
            ->where('event_slug', $eventSlug);

        if ($referenceValue !== null && $referenceValue !== '') {
            $query->where('reference_value', $referenceValue);
        }
        // Calendar-day filter on stored created_date (TMS log created_date).
        // Use DATE() so timezone-shifted startOfDay/endOfDay cannot drop a whole day.
        if ($dateFrom !== null && $dateFrom !== '') {
            $query->whereDate('created_date', '>=', $dateFrom);
        }
        if ($dateTo !== null && $dateTo !== '') {
            $query->whereDate('created_date', '<=', $dateTo);
        }

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $rows = $query
            ->orderByDesc('created_date')
            ->orderByDesc('api_request_log_id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get([
                'api_request_log_id',
                'event_slug',
                'reference_value',
                'created_date',
            ]);

        $data = $rows->map(fn ($r) => [
            'api_request_log_id' => (int) $r->api_request_log_id,
            'event_slug' => $r->event_slug,
            'reference_value' => $r->reference_value,
            'created_date' => optional($r->created_date)?->toDateTimeString(),
        ])->values()->all();

        return [
            'data' => $data,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ];
    }

    public function deleteOlderThan(string $environment, string $date): int
    {
        return ApiRequestLogViewer::query()
            ->where('environment', $environment)
            ->whereDate('created_date', '<', $date)
            ->delete();
    }

    public function tryAdvisoryLock(int $key): bool
    {
        $row = DB::selectOne('SELECT pg_try_advisory_lock(?) AS locked', [$key]);

        return (bool) ($row->locked ?? false);
    }

    public function advisoryUnlock(int $key): void
    {
        DB::select('SELECT pg_advisory_unlock(?)', [$key]);
    }
}
