<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\Import\LocationImporter;
use App\Support\ArmosEnvironment;
use Illuminate\Http\Request;
use Throwable;

class ImportLokasiController extends Controller
{
    use RespondsWithApi;

    public function __construct(private LocationImporter $importer) {}

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        try {
            $env = ArmosEnvironment::apiEnv();
            $result = $this->importer->import($request->file('file'), $env);

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Import selesai',
                'messages' => $result['messages'] ?? [],
                'log_data_filename' => $result['log_data_filename'] ?? null,
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }

    public function downloadLog(Request $request)
    {
        $request->validate(['filename' => ['required', 'string']]);

        try {
            $path = $this->importer->pathForLog($request->string('filename')->toString());

            return response()->download($path);
        } catch (Throwable $e) {
            return $this->fromException($e, 404);
        }
    }
}
