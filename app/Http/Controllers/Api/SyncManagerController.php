<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Sync\SyncManagerService;
use Illuminate\Http\Request;
use Throwable;

class SyncManagerController extends Controller
{
    use RespondsWithApi;

    public function __construct(private SyncManagerService $service) {}

    public function run(Request $request)
    {
        $data = $request->validate([
            'sync_type' => ['required', 'in:fact_order,fact_delivery,both'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        try {
            $jobId = $this->service->run($data['sync_type'], $data['date_from'] ?? null, $data['date_to'] ?? null);

            return response()->json(['status' => 200, 'job_id' => $jobId]);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function job(string $jobId)
    {
        try {
            $job = $this->service->job($jobId);

            return response()->json([
                'status' => 200,
                'job' => [
                    'job_id' => $job['job_id'] ?? $jobId,
                    'status' => $job['status'] ?? strtoupper((string) ($job['state'] ?? 'RUNNING')),
                    'error' => $job['error'] ?? null,
                    'progress' => $job['progress'] ?? null,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->fromException($e, 404);
        }
    }

    public function status(Request $request)
    {
        try {
            $result = $this->service->status(
                $request->query('sync_type'),
                (int) $request->query('page', 1),
                (int) $request->query('limit', 20),
            );

            return response()->json(array_merge(['status' => 200], $result));
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }
}
