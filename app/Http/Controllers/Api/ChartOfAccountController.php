<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Account::with('parent', 'children')->orderBy('code');
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', '%' . $term . '%')
                    ->orWhere('name', 'like', '%' . $term . '%')
                    ->orWhere('description', 'like', '%' . $term . '%');
            });
        }
        if ($request->boolean('tree')) {
            $accounts = $query->get();
            $tree = $this->buildTree($accounts->toArray());
            return response()->json(['data' => $tree]);
        }
        $accounts = $query->get();
        return response()->json(['data' => $accounts]);
    }

    private function buildTree(array $accounts, ?int $parentId = null): array
    {
        $branch = [];
        foreach ($accounts as $account) {
            $pid = isset($account['parent_id']) ? (int) $account['parent_id'] : null;
            if ($pid === $parentId) {
                $children = $this->buildTree($accounts, (int) $account['id']);
                $account['children'] = $children;
                $branch[] = $account;
            }
        }
        return $branch;
    }

    public function balance(Request $request, Account $account): JsonResponse
    {
        $asOf = $request->get('as_of', now()->format('Y-m-d'));
        $companyId = session('current_company_id');
        $totals = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.date', '<=', $asOf)
            ->where('journal_entry_lines.account_id', $account->id)
            ->selectRaw('SUM(journal_entry_lines.debit) as total_debit, SUM(journal_entry_lines.credit) as total_credit')
            ->first();
        $debit = (float) ($totals->total_debit ?? 0);
        $credit = (float) ($totals->total_credit ?? 0);
        $balance = $account->isDebitPositive() ? ($debit - $credit) : ($credit - $debit);
        return response()->json([
            'data' => [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'as_of' => $asOf,
                'total_debit' => $debit,
                'total_credit' => $credit,
                'balance' => $balance,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'parent_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
        ]);
        $validated['company_id'] = session('current_company_id');
        $account = Account::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create($validated);
        return response()->json(['data' => $account], 201);
    }

    public function show(Account $account): JsonResponse
    {
        $account->load('parent', 'children');
        return response()->json(['data' => $account]);
    }

    public function update(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:asset,liability,equity,income,expense',
            'parent_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
        ]);
        $account->update($validated);
        return response()->json(['data' => $account]);
    }

    public function destroy(Account $account): JsonResponse
    {
        if ($account->is_system) {
            return response()->json(['message' => 'Cannot delete system account'], 422);
        }
        $account->delete();
        return response()->json(null, 204);
    }
}
