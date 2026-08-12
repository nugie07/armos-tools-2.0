<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Tms\ProductToRouteService;
use Illuminate\Http\Request;
use Throwable;

class ProductToRouteController extends Controller
{
    use RespondsWithApi;

    public function __construct(private ProductToRouteService $service) {}

    public function index(Request $request)
    {
        $request->validate([
            'sku' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        try {
            return $this->ok($this->service->fetch(
                $request->string('sku')->toString(),
                $request->string('start_date')->toString(),
                $request->string('end_date')->toString(),
            ));
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }
}
