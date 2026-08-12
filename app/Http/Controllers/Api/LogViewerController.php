<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Log\LogViewerService;
use Illuminate\Http\Request;
use Throwable;

class LogViewerController extends Controller
{
    use RespondsWithApi;

    public function __construct(private LogViewerService $service) {}

    public function folders()
    {
        return $this->ok($this->service->folders());
    }

    public function events()
    {
        return $this->ok($this->service->events());
    }

    public function search(Request $request)
    {
        try {
            $result = $this->service->search(
                folder: (string) $request->query('folder', ''),
                event: (string) $request->query('event', ''),
                requestFilter: $request->query('request'),
                searchField: $request->query('search_field'),
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', $request->query('limit', 15)),
            );

            return response()->json(array_merge(['status' => 200], $result));
        } catch (Throwable $e) {
            return $this->fromException($e, 400);
        }
    }
}
