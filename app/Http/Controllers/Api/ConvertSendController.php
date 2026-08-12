<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\ConvertSend\ConvertSendService;
use Illuminate\Http\Request;
use Throwable;

class ConvertSendController extends Controller
{
    use RespondsWithApi;

    public function __construct(private ConvertSendService $service) {}

    public function store(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        try {
            $result = $this->service->handle($request->file('file'));

            return $this->ok($result['converted_json'] ?? null, 'Convert & send selesai', [
                'steps' => $result['steps'] ?? [],
                'converted_json' => $result['converted_json'] ?? null,
            ]);
        } catch (Throwable $e) {
            return $this->fromException($e);
        }
    }
}
