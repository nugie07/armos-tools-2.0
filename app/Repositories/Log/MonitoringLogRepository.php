<?php

namespace App\Repositories\Log;

use App\Models\ApiRequestLogViewer;
use Carbon\CarbonInterface;
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
     * Cursor/keyset pagination on monitoring index.
     *
     * @return array{data:list<array<string,mixed>>,next_cursor:?string,has_more:bool}
     */
    public function search(
        string $environment,
        string $eventSlug,
        ?string $referenceValue,
        ?CarbonInterface $dateFrom,
        ?CarbonInterface $dateTo,
        ?string $cursor,
        int $perPage,
    ): array {
        $perPage = max(1, min(100, $perPage));
        $query = ApiRequestLogViewer::query()
            ->where('environment', $environment)
            ->where('event_slug', $eventSlug);

        if ($referenceValue !== null && $referenceValue !== '') {
            $query->where('reference_value', $referenceValue);
        }
        if ($dateFrom !== null) {
            $query->where('created_date', '>=', $dateFrom->startOfDay());
        }
        if ($dateTo !== null) {
            $query->where('created_date', '<=', $dateTo->endOfDay());
        }

        if ($cursor) {
            $decoded = $this->decodeCursor($cursor);
            if ($decoded !== null) {
                $query->where(function ($q) use ($decoded) {
                    $q->where('created_date', '<', $decoded['created_date'])
                        ->orWhere(function ($q2) use ($decoded) {
                            $q2->where('created_date', '=', $decoded['created_date'])
                                ->where('api_request_log_id', '<', $decoded['api_request_log_id']);
                        });
                });
            }
        }

        $rows = $query
            ->orderByDesc('created_date')
            ->orderByDesc('api_request_log_id')
            ->limit($perPage + 1)
            ->get([
                'api_request_log_id',
                'event_slug',
                'reference_value',
                'created_date',
            ]);

        $hasMore = $rows->count() > $perPage;
        if ($hasMore) {
            $rows = $rows->take($perPage);
        }

        $data = $rows->map(fn ($r) => [
            'api_request_log_id' => (int) $r->api_request_log_id,
            'event_slug' => $r->event_slug,
            'reference_value' => $r->reference_value,
            'created_date' => optional($r->created_date)?->toDateTimeString(),
        ])->values()->all();

        $nextCursor = null;
        if ($hasMore && $data !== []) {
            $last = $rows->last();
            $nextCursor = $this->encodeCursor(
                (string) optional($last->created_date)?->format('Y-m-d H:i:s.u'),
                (int) $last->api_request_log_id
            );
        }

        return [
            'data' => $data,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    protected function encodeCursor(string $createdDate, int $id): string
    {
        return rtrim(strtr(base64_encode($createdDate.'|'.$id), '+/', '-_'), '=');
    }

    /**
     * @return array{created_date:string,api_request_log_id:int}|null
     */
    protected function decodeCursor(string $cursor): ?array
    {
        $raw = base64_decode(strtr($cursor, '-_', '+/'), true);
        if ($raw === false || ! str_contains($raw, '|')) {
            return null;
        }
        [$date, $id] = explode('|', $raw, 2);
        if ($date === '' || ! ctype_digit((string) $id)) {
            return null;
        }

        return [
            'created_date' => $date,
            'api_request_log_id' => (int) $id,
        ];
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
