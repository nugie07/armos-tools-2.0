<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Jobs\SyncApiRequestLogsJob;
use App\Services\Log\LogSyncService;
use App\Services\Log\LogViewerService;
use App\Support\ArmosEnvironment;
use Illuminate\Http\Request;
use Throwable;

class LogViewerController extends Controller
{
    use RespondsWithApi;

    public function __construct(
        private LogViewerService $viewer,
        private LogSyncService $sync,
    ) {}

    public function events()
    {
        return $this->ok($this->viewer->events());
    }

    public function index(Request $request)
    {
        try {
            $result = $this->viewer->search(
                eventSlug: (string) $request->query('event_slug', $request->query('event', '')),
                referenceValue: $request->query('reference_value', $request->query('request')),
                dateFrom: $request->query('date_from'),
                dateTo: $request->query('date_to'),
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 15),
            );

            return response()->json(array_merge([
                'status' => 200,
                'message' => null,
            ], $result));
        } catch (Throwable $e) {
            return $this->fromException($e, 400);
        }
    }

    public function show(int $apiRequestLogId)
    {
        try {
            return $this->ok($this->viewer->detail($apiRequestLogId));
        } catch (Throwable $e) {
            return $this->fromException($e, 404);
        }
    }

    public function syncStatus()
    {
        try {
            return $this->ok($this->sync->statusPayload(ArmosEnvironment::apiEnv()));
        } catch (Throwable $e) {
            return $this->fromException($e, 400);
        }
    }

    public function sync(Request $request)
    {
        try {
            $environment = ArmosEnvironment::apiEnv();
            $gate = $this->sync->canStartManualSync($environment);
            if (! $gate['allowed']) {
                $http = $gate['status'] === 'running' ? 409 : 429;

                return response()->json([
                    'status' => $http,
                    'success' => false,
                    'message' => $gate['message'],
                    'data' => [
                        'status' => $gate['status'],
                        'next_sync_at' => $gate['next_sync_at'],
                    ],
                ], $http);
            }

            SyncApiRequestLogsJob::dispatch($environment, 'manual');

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Log synchronization started.',
                'data' => [
                    'status' => 'running',
                    'environment' => $environment,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->fromException($e, 400);
        }
    }
}
