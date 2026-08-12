<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Tms\QtyUnloadingService;
use Illuminate\Http\Request;
use Throwable;

class QtyUnloadingController extends Controller
{
    use RespondsWithApi;

    public function __construct(private QtyUnloadingService $service) {}

    public function warehouses()
    {
        try {
            return $this->ok($this->service->fetchWarehouses());
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function find(Request $request)
    {
        $request->validate([
            'warehouse_id' => ['required'],
            'faktur_id' => ['required', 'string'],
            'sku' => ['required', 'string'],
        ]);

        try {
            $rows = $this->service->find(
                $request->input('warehouse_id'),
                $request->string('faktur_id')->toString(),
                $request->string('sku')->toString(),
            );
            if (! $rows) {
                return $this->fail('Data tidak ditemukan', 404);
            }

            return $this->ok($rows[0]);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'order_detail_id' => ['required', 'integer'],
            'quantity_unloading' => ['required', 'numeric'],
        ]);

        try {
            $affected = $this->service->update((int) $request->input('order_detail_id'), $request->input('quantity_unloading'));

            return $this->ok(null, null, ['affected' => $affected]);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }
}
