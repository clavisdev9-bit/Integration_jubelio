<?php

namespace App\Http\Controllers\Api\Odoo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OdooService;

class OdooDebugController extends Controller
{
    public function execute(
        Request $request,
        OdooService $odoo
    ) {

        $validated = $request->validate([
            'model' => 'required|string',
            'method' => 'required|string',
            'args' => 'nullable|array',
            'kwargs' => 'nullable|array',
        ]);

        $result = $odoo->execute(
            $validated['model'],
            $validated['method'],
            $validated['args'] ?? [],
            $validated['kwargs'] ?? [],
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}