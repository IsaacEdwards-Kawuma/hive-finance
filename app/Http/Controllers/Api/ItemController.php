<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Item::with('category', 'saleTax', 'purchaseTax')
            ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%{$v}%")->orWhere('sku', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate($request->get('per_page', 15));
        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:100',
            'sale_price' => 'numeric|min:0',
            'purchase_price' => 'numeric|min:0',
            'sale_tax_id' => 'nullable|exists:tax_rates,id',
            'purchase_tax_id' => 'nullable|exists:tax_rates,id',
            'item_category_id' => 'nullable|exists:item_categories,id',
            'track_quantity' => 'boolean',
        ]);
        $validated['company_id'] = session('current_company_id');
        $validated['sale_price'] = $validated['sale_price'] ?? 0;
        $validated['purchase_price'] = $validated['purchase_price'] ?? 0;
        $validated['track_quantity'] = $validated['track_quantity'] ?? false;
        $item = Item::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create($validated);
        return response()->json(['data' => $item], 201);
    }

    public function show(Item $item): JsonResponse
    {
        $item->load('category', 'saleTax', 'purchaseTax');
        return response()->json(['data' => $item]);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        $item->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:100',
            'sale_price' => 'numeric|min:0',
            'purchase_price' => 'numeric|min:0',
            'sale_tax_id' => 'nullable|exists:tax_rates,id',
            'purchase_tax_id' => 'nullable|exists:tax_rates,id',
            'item_category_id' => 'nullable|exists:item_categories,id',
            'track_quantity' => 'boolean',
        ]));
        return response()->json(['data' => $item]);
    }

    public function destroy(Item $item): JsonResponse
    {
        $item->delete();
        return response()->json(null, 204);
    }
}
