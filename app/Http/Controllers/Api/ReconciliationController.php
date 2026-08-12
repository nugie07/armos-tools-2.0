<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Tms\ReconciliationService;
use Illuminate\Http\Request;
use Throwable;

class ReconciliationController extends Controller
{
    use RespondsWithApi;

    public function __construct(private ReconciliationService $service) {}

    public function index(Request $request)
    {
        $fakturId = trim((string) $request->query('faktur_id', ''));
        if ($fakturId === '') {
            return $this->fail('faktur_id required', 400);
        }

        try {
            $rows = $this->service->fetchByFaktur($fakturId);
            if (! $rows) {
                return $this->fail('Data tidak ditemukan', 404);
            }

            return $this->ok($rows);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function uncheck(Request $request)
    {
        $orderId = $request->input('order_id');
        if (! is_numeric($orderId)) {
            return $this->fail('order_id required', 400);
        }

        try {
            $affected = $this->service->deleteByOrderId((int) $orderId);

            return $this->ok(null, null, ['affected' => $affected]);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }
}
