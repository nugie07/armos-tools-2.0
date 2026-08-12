<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Tms\DriverCostService;
use Illuminate\Http\Request;
use Throwable;

class DriverCostController extends Controller
{
    use RespondsWithApi;

    public function __construct(private DriverCostService $service) {}

    public function list(Request $request)
    {
        $manifest = trim((string) $request->query('manifest_reference', ''));
        if ($manifest === '') {
            return $this->fail('manifest_reference required', 400);
        }

        try {
            $result = $this->service->list($manifest, (int) $request->query('page', 1));
            $perPage = 20;
            $pages = max(1, (int) ceil(($result['total'] ?? 0) / $perPage));

            return response()->json([
                'status' => 200,
                'data' => $result['rows'] ?? [],
                'page' => $result['page'] ?? 1,
                'pages' => $pages,
                'total' => $result['total'] ?? 0,
            ]);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function delete(Request $request)
    {
        $id = $request->input('order_cost_id');
        if (! is_numeric($id)) {
            return $this->fail('order_cost_id required', 400);
        }

        try {
            $affected = $this->service->delete((int) $id);

            return $this->ok(null, null, ['affected' => $affected]);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }
}
