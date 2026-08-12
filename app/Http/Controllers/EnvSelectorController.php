<?php

namespace App\Http\Controllers;

use App\Support\ArmosEnvironment;
use Illuminate\Http\Request;

class EnvSelectorController extends Controller
{
    public function select(Request $request)
    {
        $request->validate([
            'environment' => ['required', 'in:production,preprod'],
        ]);

        session([
            ArmosEnvironment::SESSION_KEY => $request->string('environment')->toString(),
        ]);

        $label = $request->input('environment') === 'production'
            ? 'Production'
            : 'Pre Production';

        return back()->with('success', "Environment aktif: {$label}");
    }
}
