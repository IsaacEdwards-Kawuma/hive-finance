<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BankTransaction::with('bankAccount')->orderByDesc('date');
        if ($request->bank_account_id) {
            $query->where('bank_account_id', $request->bank_account_id);
        }
        if ($request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }
        $transactions = $query->paginate($request->get('per_page', 15));
        return response()->json($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'date' => 'required|date',
            'type' => 'required|in:deposit,withdrawal,transfer',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'reference' => 'nullable|string|max:100',
        ]);
        $validated['company_id'] = session('current_company_id');
        if ($validated['type'] === 'withdrawal') {
            $validated['amount'] = -abs($validated['amount']);
        } else {
            $validated['amount'] = abs($validated['amount']);
        }
        $transaction = BankTransaction::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create($validated);
        return response()->json(['data' => $transaction], 201);
    }

    public function show(BankTransaction $bankTransaction): JsonResponse
    {
        $bankTransaction->load('bankAccount', 'journalEntry');
        return response()->json(['data' => $bankTransaction]);
    }

    public function update(Request $request, BankTransaction $bankTransaction): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'sometimes|date',
            'description' => 'nullable|string',
            'reference' => 'nullable|string|max:100',
            'reconciled' => 'boolean',
        ]);
        if (array_key_exists('reconciled', $validated)) {
            $validated['reconciled_at'] = $validated['reconciled'] ? now() : null;
        }
        $bankTransaction->update($validated);
        return response()->json(['data' => $bankTransaction]);
    }

    public function destroy(BankTransaction $bankTransaction): JsonResponse
    {
        $bankTransaction->delete();
        return response()->json(null, 204);
    }
}
