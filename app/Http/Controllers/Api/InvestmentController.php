<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Investment::with('account')->orderBy('name');
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('symbol', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }
        $items = $query->paginate($request->get('per_page', 50));
        return response()->json($items);
    }

    public function summary(): JsonResponse
    {
        $totals = Investment::selectRaw('
            COALESCE(SUM(cost_basis), 0) as total_cost,
            COALESCE(SUM(current_value), 0) as total_value,
            COUNT(*) as count
        ')->first();
        $totalCost = (float) ($totals->total_cost ?? 0);
        $totalValue = (float) ($totals->total_value ?? 0);
        return response()->json([
            'data' => [
                'total_cost_basis' => $totalCost,
                'total_current_value' => $totalValue,
                'total_gain_loss' => $totalValue - $totalCost,
                'total_gain_loss_percent' => $totalCost > 0 ? round((($totalValue - $totalCost) / $totalCost) * 100, 2) : null,
                'count' => (int) ($totals->count ?? 0),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:stock,bond,mutual_fund,real_estate,crypto,other',
            'symbol' => 'nullable|string|max:50',
            'cost_basis' => 'required|numeric|min:0',
            'current_value' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'date_acquired' => 'nullable|date',
            'account_id' => 'nullable|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);
        $validated['company_id'] = session('current_company_id');
        $validated['currency'] = $validated['currency'] ?? 'USD';
        $investment = Investment::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create($validated);
        $investment->load('account');
        return response()->json(['data' => $investment], 201);
    }

    public function show(Investment $investment): JsonResponse
    {
        $investment->load('account');
        return response()->json(['data' => $investment]);
    }

    public function update(Request $request, Investment $investment): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:stock,bond,mutual_fund,real_estate,crypto,other',
            'symbol' => 'nullable|string|max:50',
            'cost_basis' => 'sometimes|numeric|min:0',
            'current_value' => 'sometimes|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'date_acquired' => 'nullable|date',
            'account_id' => 'nullable|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);
        $investment->update($validated);
        $investment->load('account');
        return response()->json(['data' => $investment]);
    }

    public function destroy(Investment $investment): JsonResponse
    {
        $investment->delete();
        return response()->json(null, 204);
    }
}
