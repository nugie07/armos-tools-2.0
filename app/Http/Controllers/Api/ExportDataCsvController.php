<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Tms\ExportDataCsvService;
use App\Support\ArmosEnvironment;
use Illuminate\Http\Request;
use Throwable;

class ExportDataCsvController extends Controller
{
    use RespondsWithApi;

    public function __construct(private ExportDataCsvService $service) {}

    public function generate(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:dataproduct,datavehicle,lovconfig,masterlocation,childlocation'],
            'data_type' => ['nullable', 'in:dataproduct,datavehicle,lovconfig,masterlocation,childlocation'],
        ]);

        $type = $data['type'] ?? $data['data_type'] ?? null;
        if (! $type) {
            return $this->fail('type / data_type required', 400);
        }

        try {
            $env = ArmosEnvironment::apiEnv();
            $filename = $this->service->generate($env, $type);

            return response()->json(['status' => 200, 'filename' => $filename]);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function download(Request $request)
    {
        $request->validate(['filename' => ['required', 'string']]);

        try {
            return response()->download($this->service->pathFor($request->string('filename')->toString()));
        } catch (Throwable $e) {
            return $this->fromException($e, 404);
        }
    }
}
