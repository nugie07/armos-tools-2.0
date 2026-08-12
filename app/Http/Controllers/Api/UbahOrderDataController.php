<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Tms\UbahOrderDataService;
use App\Support\ArmosEnvironment;
use Illuminate\Http\Request;
use Throwable;

class UbahOrderDataController extends Controller
{
    use RespondsWithApi;

    public function __construct(private UbahOrderDataService $service) {}

    public function search(Request $request)
    {
        $request->validate([
            'order_number' => ['required', 'string'],
        ]);

        try {
            $env = ArmosEnvironment::apiEnv();

            return $this->ok($this->service->findByOrderNumber(
                $env,
                $request->string('order_number')->toString(),
            ));
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'status' => ['nullable', 'in:'.implode(',', UbahOrderDataService::VALID_STATUS)],
            'order_integration_id' => ['nullable', 'string'],
            'delivery_date' => ['nullable', 'date'],
        ]);

        try {
            $env = ArmosEnvironment::apiEnv();
            $affected = $this->service->update(
                $env,
                (int) $data['order_id'],
                [
                    'status' => $data['status'] ?? null,
                    'order_integration_id' => $data['order_integration_id'] ?? null,
                    'delivery_date' => $data['delivery_date'] ?? null,
                ],
                (string) (auth()->user()->nama ?? 'armos-tools'),
            );

            return $this->ok(null, null, ['affected' => $affected]);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }
}
