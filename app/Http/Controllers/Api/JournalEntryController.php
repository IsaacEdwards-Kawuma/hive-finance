<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JournalEntry::with('lines.account')->orderByDesc('date');
        if ($request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', $term)
                    ->orWhere('reference', 'like', $term);
            });
        }
        $entries = $query->paginate($request->get('per_page', 50));
        return response()->json($entries);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'nullable|string',
            'reference' => 'nullable|string|max:100',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'required_without:lines.*.credit|numeric|min:0',
            'lines.*.credit' => 'required_without:lines.*.debit|numeric|min:0',
            'lines.*.memo' => 'nullable|string',
        ]);
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($validated['lines'] as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }
        if (abs($totalDebit - $totalCredit) >= 0.01) {
            return response()->json(['message' => 'Journal entry must balance (debits = credits)'], 422);
        }
        $companyId = session('current_company_id');
        $entry = JournalEntry::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create([
            'company_id' => $companyId,
            'number' => JournalEntry::withoutGlobalScope(\App\Scopes\CompanyScope::class)->where('company_id', $companyId)->max('id') + 1,
            'date' => $validated['date'],
            'description' => $validated['description'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'status' => 'draft',
            'created_by' => $request->user()?->id,
        ]);
        foreach ($validated['lines'] as $line) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $line['account_id'],
                'debit' => (float) ($line['debit'] ?? 0),
                'credit' => (float) ($line['credit'] ?? 0),
                'memo' => $line['memo'] ?? null,
            ]);
        }
        $entry->load('lines.account');
        return response()->json(['data' => $entry], 201);
    }

    public function show(JournalEntry $journalEntry): JsonResponse
    {
        $journalEntry->load('lines.account', 'creator');
        return response()->json(['data' => $journalEntry]);
    }

    public function update(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        if ($journalEntry->status === 'posted') {
            return response()->json(['message' => 'Cannot edit posted entry'], 422);
        }
        $validated = $request->validate([
            'date' => 'sometimes|date',
            'description' => 'nullable|string',
            'reference' => 'nullable|string|max:100',
            'lines' => 'sometimes|array|min:2',
            'lines.*.account_id' => 'required_with:lines|exists:accounts,id',
            'lines.*.debit' => 'numeric|min:0',
            'lines.*.credit' => 'numeric|min:0',
            'lines.*.memo' => 'nullable|string',
        ]);
        if (isset($validated['lines'])) {
            $totalDebit = $totalCredit = 0;
            foreach ($validated['lines'] as $line) {
                $totalDebit += (float) ($line['debit'] ?? 0);
                $totalCredit += (float) ($line['credit'] ?? 0);
            }
            if (abs($totalDebit - $totalCredit) >= 0.01) {
                return response()->json(['message' => 'Journal entry must balance'], 422);
            }
            $journalEntry->lines()->delete();
            foreach ($validated['lines'] as $line) {
                $journalEntry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => (float) ($line['debit'] ?? 0),
                    'credit' => (float) ($line['credit'] ?? 0),
                    'memo' => $line['memo'] ?? null,
                ]);
            }
        }
        $journalEntry->update(array_filter([
            'date' => $validated['date'] ?? null,
            'description' => $validated['description'] ?? null,
            'reference' => $validated['reference'] ?? null,
        ]));
        $journalEntry->load('lines.account');
        return response()->json(['data' => $journalEntry]);
    }

    public function destroy(JournalEntry $journalEntry): JsonResponse
    {
        if ($journalEntry->status === 'posted') {
            return response()->json(['message' => 'Cannot delete posted entry'], 422);
        }
        $journalEntry->lines()->delete();
        $journalEntry->delete();
        return response()->json(null, 204);
    }

    public function post(JournalEntry $journalEntry): JsonResponse
    {
        if ($journalEntry->status === 'posted') {
            return response()->json(['message' => 'Already posted'], 422);
        }
        if (!$journalEntry->isBalanced()) {
            return response()->json(['message' => 'Entry must balance before posting'], 422);
        }
        $journalEntry->update(['status' => 'posted', 'posted_at' => now()]);
        return response()->json(['data' => $journalEntry]);
    }
}
