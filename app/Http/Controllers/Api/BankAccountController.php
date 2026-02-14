<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index(): JsonResponse
    {
        $accounts = BankAccount::with('account')->orderBy('name')->get();
        return response()->json(['data' => $accounts]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_id' => 'required|exists:accounts,id',
            'number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'currency' => 'string|max:3',
            'opening_balance' => 'numeric',
            'opening_balance_date' => 'nullable|date',
        ]);
        $validated['company_id'] = session('current_company_id');
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $account = BankAccount::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create($validated);
        return response()->json(['data' => $account], 201);
    }

    public function show(BankAccount $bankAccount): JsonResponse
    {
        $bankAccount->load('account', 'transactions');
        return response()->json(['data' => $bankAccount]);
    }

    public function update(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $bankAccount->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'currency' => 'sometimes|string|max:3',
            'opening_balance' => 'numeric',
            'opening_balance_date' => 'nullable|date',
            'enabled' => 'boolean',
        ]));
        return response()->json(['data' => $bankAccount]);
    }

    public function destroy(BankAccount $bankAccount): JsonResponse
    {
        if ($bankAccount->transactions()->exists()) {
            return response()->json(['message' => 'Cannot delete account with transactions'], 422);
        }
        $bankAccount->delete();
        return response()->json(null, 204);
    }
}
