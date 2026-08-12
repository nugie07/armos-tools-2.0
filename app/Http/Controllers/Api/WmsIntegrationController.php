<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Tms\WmsIntegrationService;
use Illuminate\Http\Request;
use Throwable;

class WmsIntegrationController extends Controller
{
    use RespondsWithApi;

    public function __construct(private WmsIntegrationService $service) {}

    public function index(Request $request)
    {
        $fakturId = trim((string) $request->query('faktur_id', ''));
        if ($fakturId === '') {
            return $this->fail('faktur_id required', 400);
        }

        try {
            return $this->ok($this->service->fetchByFaktur($fakturId));
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function update(Request $request)
    {
        $orderId = $request->input('order_id');
        $integrationId = $request->input('order_integration_id');
        if (! is_numeric($orderId) || $integrationId === null || $integrationId === '') {
            return $this->fail('Invalid payload', 400);
        }

        try {
            $affected = $this->service->update((int) $orderId, (string) $integrationId);

            return $this->ok(null, null, ['affected' => $affected]);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }
}
