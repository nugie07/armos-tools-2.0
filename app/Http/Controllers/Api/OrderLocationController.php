<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Tms\OrderLocationService;
use Illuminate\Http\Request;
use Throwable;

class OrderLocationController extends Controller
{
    use RespondsWithApi;

    public function __construct(private OrderLocationService $service) {}

    public function warehouses()
    {
        try {
            return $this->ok($this->service->fetchWarehouses());
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function orders(Request $request)
    {
        $fakturId = trim((string) $request->query('faktur_id', ''));
        $warehouseId = trim((string) $request->query('warehouse_id', ''));
        if ($fakturId === '' || ! ctype_digit($warehouseId)) {
            return $this->fail('Invalid parameters', 400);
        }

        try {
            return $this->ok($this->service->fetchOrdersByFakturAndWarehouse($fakturId, (int) $warehouseId));
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function locations()
    {
        try {
            return $this->ok($this->service->fetchAllLocations());
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function updateLocation(Request $request)
    {
        $fakturId = trim((string) $request->input('faktur_id', ''));
        $customerId = $request->input('customer_id');
        if ($fakturId === '' || ! is_numeric($customerId)) {
            return $this->fail('Invalid payload', 400);
        }

        try {
            $affected = $this->service->updateOrderCustomerLocation($fakturId, (int) $customerId);

            return $this->ok(null, null, ['affected' => $affected]);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }
}
