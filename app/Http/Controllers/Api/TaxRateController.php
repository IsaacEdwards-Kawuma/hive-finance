<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function index(): JsonResponse
    {
        $rates = TaxRate::orderBy('name')->get();
        return response()->json(['data' => $rates]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'in:normal,inclusive,compound',
            'enabled' => 'boolean',
        ]);
        $validated['company_id'] = session('current_company_id');
        $validated['type'] = $validated['type'] ?? 'normal';
        $validated['enabled'] = $validated['enabled'] ?? true;
        $tax = TaxRate::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create($validated);
        return response()->json(['data' => $tax], 201);
    }

    public function show(TaxRate $taxRate): JsonResponse
    {
        return response()->json(['data' => $taxRate]);
    }

    public function update(Request $request, TaxRate $taxRate): JsonResponse
    {
        $taxRate->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'rate' => 'sometimes|numeric|min:0|max:100',
            'type' => 'in:normal,inclusive,compound',
            'enabled' => 'boolean',
        ]));
        return response()->json(['data' => $taxRate]);
    }

    public function destroy(TaxRate $taxRate): JsonResponse
    {
        $taxRate->delete();
        return response()->json(null, 204);
    }
}
