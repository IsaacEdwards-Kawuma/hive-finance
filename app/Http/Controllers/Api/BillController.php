<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Bill::with('vendor')->orderByDesc('bill_date');
        if ($request->vendor_id) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $bills = $query->paginate($request->get('per_page', 15));
        return response()->json($bills);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
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
        $lastNum = Bill::withoutGlobalScope(\App\Scopes\CompanyScope::class)->where('company_id', $companyId)->max('id');
        $bill = Bill::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create([
            'company_id' => $companyId,
            'vendor_id' => $validated['vendor_id'],
            'bill_number' => 'BILL-' . (($lastNum ?? 0) + 1),
            'bill_date' => $validated['bill_date'],
            'due_date' => $validated['due_date'],
            'currency' => $validated['currency'] ?? 'USD',
            'exchange_rate' => 1,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);
        $subtotal = $taxTotal = $discountTotal = 0;
        foreach ($validated['items'] as $i => $item) {
            $total = $item['quantity'] * $item['price'] + ($item['tax'] ?? 0) - ($item['discount'] ?? 0);
            $subtotal += $item['quantity'] * $item['price'];
            $taxTotal += $item['tax'] ?? 0;
            $discountTotal += $item['discount'] ?? 0;
            $bill->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'tax' => $item['tax'] ?? 0,
                'discount' => $item['discount'] ?? 0,
                'total' => $total,
                'sort_order' => $i,
            ]);
        }
        $bill->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'total' => $subtotal + $taxTotal - $discountTotal,
        ]);
        $bill->load('items', 'vendor');
        return response()->json(['data' => $bill], 201);
    }

    public function show(Bill $bill): JsonResponse
    {
        $bill->load('items', 'vendor', 'payments');
        return response()->json(['data' => $bill]);
    }

    public function duplicate(Bill $bill): JsonResponse
    {
        $companyId = session('current_company_id');
        $lastNum = Bill::withoutGlobalScope(\App\Scopes\CompanyScope::class)->where('company_id', $companyId)->max('id');
        $newBill = $bill->replicate(['id']);
        $newBill->bill_number = 'BILL-' . (($lastNum ?? 0) + 1);
        $newBill->bill_date = now()->format('Y-m-d');
        $newBill->due_date = now()->addDays(30)->format('Y-m-d');
        $newBill->status = 'draft';
        $newBill->save();
        foreach ($bill->items as $item) {
            $newBill->items()->create($item->only('description', 'quantity', 'price', 'tax', 'discount', 'total', 'sort_order'));
        }
        $newBill->load('items', 'vendor');
        return response()->json(['data' => $newBill], 201);
    }

    public function update(Request $request, Bill $bill): JsonResponse
    {
        if ($bill->status !== 'draft') {
            return response()->json(['message' => 'Can only edit draft bills'], 422);
        }
        $validated = $request->validate([
            'bill_date' => 'sometimes|date',
            'due_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.description' => 'required_with:items|string',
            'items.*.quantity' => 'required_with:items|numeric|min:0',
            'items.*.price' => 'required_with:items|numeric|min:0',
        ]);
        if (isset($validated['items'])) {
            $bill->items()->delete();
            $subtotal = $taxTotal = $discountTotal = 0;
            foreach ($validated['items'] as $i => $item) {
                $total = $item['quantity'] * $item['price'] + ($item['tax'] ?? 0) - ($item['discount'] ?? 0);
                $subtotal += $item['quantity'] * $item['price'];
                $taxTotal += $item['tax'] ?? 0;
                $discountTotal += $item['discount'] ?? 0;
                $bill->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'tax' => $item['tax'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'total' => $total,
                    'sort_order' => $i,
                ]);
            }
            $bill->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'total' => $subtotal + $taxTotal - $discountTotal,
            ]);
        }
        $bill->update(array_filter([
            'bill_date' => $validated['bill_date'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]));
        $bill->load('items', 'vendor');
        return response()->json(['data' => $bill]);
    }

    public function destroy(Bill $bill): JsonResponse
    {
        if ($bill->status !== 'draft') {
            return response()->json(['message' => 'Can only delete draft bills'], 422);
        }
        $bill->items()->delete();
        $bill->delete();
        return response()->json(null, 204);
    }

    public function recordPayment(Request $request, Bill $bill): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:100',
        ]);
        $amount = (float) $validated['amount'];
        if ($amount > $bill->balance_due) {
            return response()->json(['message' => 'Amount exceeds balance due'], 422);
        }
        $companyId = session('current_company_id');
        $payment = Payment::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create([
            'company_id' => $companyId,
            'payable_type' => Bill::class,
            'payable_id' => $bill->id,
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'paid_at' => $validated['paid_at'],
            'amount' => $amount,
            'currency' => $bill->currency,
            'payment_method' => $validated['payment_method'] ?? null,
            'reference' => $validated['reference'] ?? null,
        ]);
        $newPaid = $bill->payments()->sum('amount') + $amount;
        $bill->update([
            'status' => $newPaid >= (float) $bill->total ? 'paid' : 'partial',
        ]);
        return response()->json(['data' => $payment], 201);
    }
}
