<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\InvestmentTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestmentTransactionController extends Controller
{
    public function index(Investment $investment): JsonResponse
    {
        $investment->load('transactions');
        return response()->json(['data' => $investment->transactions]);
    }

    public function store(Request $request, Investment $investment): JsonResponse
    {
        $validated = $request->validate([
            'trans_date' => 'required|date',
            'type' => 'required|string|in:buy,sell,dividend,adjustment,other',
            'quantity' => 'nullable|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);
        $validated['investment_id'] = $investment->id;
        $tx = InvestmentTransaction::create($validated);
        return response()->json(['data' => $tx], 201);
    }

    public function destroy(Investment $investment, int $transaction): JsonResponse
    {
        $tx = InvestmentTransaction::where('investment_id', $investment->id)->findOrFail($transaction);
        $tx->delete();
        return response()->json(null, 204);
    }
}
