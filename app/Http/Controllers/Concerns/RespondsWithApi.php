<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Throwable;

trait RespondsWithApi
{
    protected function ok(mixed $data = null, ?string $message = null, array $extra = [], int $http = 200): JsonResponse
    {
        return response()->json(array_merge([
            'status' => $http,
            'message' => $message,
            'data' => $data,
        ], $extra), $http);
    }

    protected function fail(string $message, int $status = 400, mixed $data = null): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function fromException(Throwable $e, int $status = 500): JsonResponse
    {
        $message = $e->getMessage();
        if (str_contains($message, 'PILIH ENVIRONMENT')) {
            $status = 400;
        } elseif ($status === 500 && str_contains($message, 'tidak lengkap')) {
            $status = 400;
        }

        report($e);

        return $this->fail($message, $status);
    }
}
