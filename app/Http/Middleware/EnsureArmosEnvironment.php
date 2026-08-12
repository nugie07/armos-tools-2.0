<?php

namespace App\Http\Middleware;

use App\Support\ArmosEnvironment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureArmosEnvironment
{
    /**
     * Env session wajib untuk semua API (termasuk Sync Manager).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! ArmosEnvironment::hasSelection()) {
            return response()->json([
                'status' => 400,
                'message' => 'PILIH ENVIRONMENT TERLEBIH DAHULU',
                'data' => null,
            ], 400);
        }

        return $next($request);
    }
}
