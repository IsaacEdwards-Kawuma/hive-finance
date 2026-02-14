<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CreditNote::with('customer', 'invoice')->orderByDesc('credit_note_date');
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('credit_note_number', 'like', '%' . $term . '%')
                    ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'like', '%' . $term . '%'));
            });
        }
        $notes = $query->paginate($request->get('per_page', 15));
        return response()->json($notes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'nullable|exists:invoices,id',
            'customer_id' => 'required|exists:customers,id',
            'credit_note_date' => 'required|date',
            'currency' => 'string|max:3',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax' => 'numeric|min:0',
            'items.*.discount' => 'numeric|min:0',
        ]);
        $companyId = session('current_company_id');
        $lastNum = CreditNote::withoutGlobalScope(\App\Scopes\CompanyScope::class)->where('company_id', $companyId)->max('id');
        $note = CreditNote::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create([
            'company_id' => $companyId,
            'invoice_id' => $validated['invoice_id'] ?? null,
            'customer_id' => $validated['customer_id'],
            'credit_note_number' => 'CN-' . (($lastNum ?? 0) + 1),
            'credit_note_date' => $validated['credit_note_date'],
            'currency' => $validated['currency'] ?? 'USD',
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);
        $subtotal = $taxTotal = $discountTotal = 0;
        foreach ($validated['items'] as $i => $item) {
            $total = $item['quantity'] * $item['price'] + ($item['tax'] ?? 0) - ($item['discount'] ?? 0);
            $subtotal += $item['quantity'] * $item['price'];
            $taxTotal += $item['tax'] ?? 0;
            $discountTotal += $item['discount'] ?? 0;
            $note->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'tax' => $item['tax'] ?? 0,
                'discount' => $item['discount'] ?? 0,
                'total' => $total,
                'sort_order' => $i,
            ]);
        }
        $note->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'total' => $subtotal + $taxTotal - $discountTotal,
        ]);
        $note->load('items', 'customer', 'invoice');
        return response()->json(['data' => $note], 201);
    }

    public function show(CreditNote $creditNote): JsonResponse
    {
        $creditNote->load('items', 'customer', 'invoice');
        return response()->json(['data' => $creditNote]);
    }

    public function apply(CreditNote $creditNote): JsonResponse
    {
        if ($creditNote->status !== 'draft') {
            return response()->json(['message' => 'Only draft credit notes can be applied'], 422);
        }
        $creditNote->update(['status' => 'applied']);
        return response()->json(['data' => $creditNote]);
    }
}
