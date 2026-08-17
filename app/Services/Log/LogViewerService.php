<?php

namespace App\Services\Log;

use App\Repositories\Log\MonitoringLogRepository;
use App\Repositories\Log\ProductionLogRepository;
use App\Support\ArmosEnvironment;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Read API for Log Viewer UI (listing from monitoring, detail from TMS).
 */
class LogViewerService
{
    public function __construct(
        private LogEventResolver $resolver,
        private MonitoringLogRepository $monitoring,
        private ProductionLogRepository $production,
    ) {}

    public function events(): array
    {
        return $this->resolver->catalog();
    }

    /**
     * @return array{data:list<array<string,mixed>>,next_cursor:?string,has_more:bool,per_page:int}
     */
    public function search(
        string $eventSlug,
        ?string $referenceValue = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $cursor = null,
        int $perPage = 50,
    ): array {
        if (! $this->resolver->isValidSlug($eventSlug)) {
            throw new InvalidArgumentException('invalid event_slug');
        }

        $environment = ArmosEnvironment::apiEnv();
        $from = $dateFrom ? Carbon::parse($dateFrom) : null;
        $to = $dateTo ? Carbon::parse($dateTo) : null;

        $searchField = $this->resolver->searchField($eventSlug);
        if ($searchField === null) {
            $referenceValue = null;
        }

        $result = $this->monitoring->search(
            $environment,
            $eventSlug,
            $referenceValue !== null ? trim($referenceValue) : null,
            $from,
            $to,
            $cursor,
            $perPage,
        );

        foreach ($result['data'] as &$row) {
            $row['event'] = $this->resolver->label($row['event_slug']);
        }
        unset($row);

        $result['per_page'] = max(1, min(100, $perPage));

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(int $apiRequestLogId): array
    {
        $environment = ArmosEnvironment::apiEnv();
        $row = $this->production->findById($environment, $apiRequestLogId);
        if ($row === null) {
            throw new InvalidArgumentException('Log tidak ditemukan di production.');
        }

        return [
            'api_request_log_id' => (int) $row['api_request_log_id'],
            'event' => $row['event'] ?? null,
            'request' => $this->maybeJson($row['request'] ?? null),
            'response' => $this->maybeJson($row['response'] ?? null),
            'created_date' => isset($row['created_date'])
                ? Carbon::parse($row['created_date'])->toDateTimeString()
                : null,
        ];
    }

    protected function maybeJson(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        $trim = trim($value);
        if ($trim === '') {
            return $value;
        }
        try {
            return json_decode($trim, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $value;
        }
    }
}
