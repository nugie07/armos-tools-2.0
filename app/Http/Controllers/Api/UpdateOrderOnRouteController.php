<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Tms\UpdateOrderOnRouteService;
use App\Support\ArmosEnvironment;
use Illuminate\Http\Request;
use Throwable;

class UpdateOrderOnRouteController extends Controller
{
    use RespondsWithApi;

    public function __construct(private UpdateOrderOnRouteService $service) {}

    public function search(Request $request)
    {
        $request->validate([
            'manifest_reference' => ['required', 'string'],
        ]);

        try {
            $env = ArmosEnvironment::apiEnv();

            return $this->ok($this->service->findByManifest(
                $env,
                $request->string('manifest_reference')->toString(),
            ));
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'manifest_reference' => ['required', 'string'],
            'status' => ['nullable', 'in:'.implode(',', UpdateOrderOnRouteService::VALID_STATUS)],
            'manifest_integration_id' => ['nullable', 'string'],
        ]);

        try {
            $env = ArmosEnvironment::apiEnv();
            $affected = $this->service->updateRoute(
                $env,
                $data['manifest_reference'],
                $data['status'] ?? null,
                $data['manifest_integration_id'] ?? null,
            );

            return $this->ok(null, null, ['affected' => $affected]);
        } catch (Throwable $e) {
            return $this->fromException($e, 400);
        }
    }
}
