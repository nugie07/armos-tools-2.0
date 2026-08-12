<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Tms\CheckOrderStatusService;
use Illuminate\Http\Request;
use Throwable;

class CheckOrderStatusController extends Controller
{
    use RespondsWithApi;

    public function __construct(private CheckOrderStatusService $service) {}

    public function orders(Request $request)
    {
        $fakturId = trim((string) $request->query('faktur_id', ''));
        if ($fakturId === '') {
            return $this->fail('faktur_id required', 400);
        }

        try {
            return $this->ok($this->service->findOrdersByFakturId($fakturId));
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function orderDetails(Request $request)
    {
        $orderId = $request->query('order_id');
        if (! is_numeric($orderId)) {
            return $this->fail('order_id required', 400);
        }

        try {
            return $this->ok($this->service->findOrderDetailsByOrderId((int) $orderId));
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function productVsInventory(Request $request)
    {
        $fakturId = trim((string) $request->query('faktur_id', ''));
        if ($fakturId === '') {
            return $this->fail('faktur_id required', 400);
        }

        try {
            return $this->ok($this->service->findProductVsInventoryByFakturId($fakturId));
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }
}
