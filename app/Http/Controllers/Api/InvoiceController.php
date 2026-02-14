<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with('customer')->orderByDesc('invoice_date');
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $invoices = $query->paginate($request->get('per_page', 15));
        return response()->json($invoices);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'currency' => 'string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax' => 'numeric|min:0',
            'items.*.discount' => 'numeric|min:0',
        ]);
        $companyId = session('current_company_id');
        $lastNum = Invoice::withoutGlobalScope(\App\Scopes\CompanyScope::class)->where('company_id', $companyId)->max('id');
        $invoice = Invoice::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create([
            'company_id' => $companyId,
            'customer_id' => $validated['customer_id'],
            'invoice_number' => 'INV-' . (($lastNum ?? 0) + 1),
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'currency' => $validated['currency'] ?? 'USD',
            'exchange_rate' => $validated['exchange_rate'] ?? 1,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);
        $subtotal = $taxTotal = $discountTotal = 0;
        foreach ($validated['items'] as $i => $item) {
            $total = $item['quantity'] * $item['price'] + ($item['tax'] ?? 0) - ($item['discount'] ?? 0);
            $subtotal += $item['quantity'] * $item['price'];
            $taxTotal += $item['tax'] ?? 0;
            $discountTotal += $item['discount'] ?? 0;
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'tax' => $item['tax'] ?? 0,
                'discount' => $item['discount'] ?? 0,
                'total' => $total,
                'sort_order' => $i,
            ]);
        }
        $invoice->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'total' => $subtotal + $taxTotal - $discountTotal,
        ]);
        $invoice->load('items', 'customer');
        return response()->json(['data' => $invoice], 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load('items', 'customer', 'payments');
        return response()->json(['data' => $invoice]);
    }

    public function duplicate(Invoice $invoice): JsonResponse
    {
        $companyId = session('current_company_id');
        $lastNum = Invoice::withoutGlobalScope(\App\Scopes\CompanyScope::class)->where('company_id', $companyId)->max('id');
        $newInv = $invoice->replicate(['id']);
        $newInv->invoice_number = 'INV-' . (($lastNum ?? 0) + 1);
        $newInv->invoice_date = now()->format('Y-m-d');
        $newInv->due_date = now()->addDays(30)->format('Y-m-d');
        $newInv->status = 'draft';
        $newInv->save();
        foreach ($invoice->items as $item) {
            $newInv->items()->create($item->only('description', 'quantity', 'price', 'tax', 'discount', 'total', 'sort_order'));
        }
        $newInv->load('items', 'customer');
        return response()->json(['data' => $newInv], 201);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        if (!in_array($invoice->status, ['draft'], true)) {
            return response()->json(['message' => 'Can only edit draft invoices'], 422);
        }
        $validated = $request->validate([
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'items' => 'sometimes|array|min:1',
            'items.*.description' => 'required_with:items|string',
            'items.*.quantity' => 'required_with:items|numeric|min:0',
            'items.*.price' => 'required_with:items|numeric|min:0',
        ]);
        if (isset($validated['items'])) {
            $invoice->items()->delete();
            $subtotal = $taxTotal = $discountTotal = 0;
            foreach ($validated['items'] as $i => $item) {
                $total = $item['quantity'] * $item['price'] + ($item['tax'] ?? 0) - ($item['discount'] ?? 0);
                $subtotal += $item['quantity'] * $item['price'];
                $taxTotal += $item['tax'] ?? 0;
                $discountTotal += $item['discount'] ?? 0;
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'tax' => $item['tax'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'total' => $total,
                    'sort_order' => $i,
                ]);
            }
            $invoice->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'total' => $subtotal + $taxTotal - $discountTotal,
            ]);
        }
        $invoice->update(array_filter([
            'invoice_date' => $validated['invoice_date'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'exchange_rate' => isset($validated['exchange_rate']) ? (float) $validated['exchange_rate'] : null,
        ]));
        $invoice->load('items', 'customer');
        return response()->json(['data' => $invoice]);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Can only delete draft invoices'], 422);
        }
        $invoice->items()->delete();
        $invoice->delete();
        return response()->json(null, 204);
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load('customer', 'items');
        $company = \App\Models\Company::withoutGlobalScope(\App\Scopes\CompanyScope::class)->find(session('current_company_id'));
        return response()->view('invoice-pdf', ['invoice' => $invoice, 'company' => $company])
            ->header('Content-Type', 'text/html');
    }

    public function recordPayment(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:100',
        ]);
        $amount = (float) $validated['amount'];
        if ($amount > $invoice->balance_due) {
            return response()->json(['message' => 'Amount exceeds balance due'], 422);
        }
        $companyId = session('current_company_id');
        $payment = Payment::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create([
            'company_id' => $companyId,
            'payable_type' => Invoice::class,
            'payable_id' => $invoice->id,
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'paid_at' => $validated['paid_at'],
            'amount' => $amount,
            'currency' => $invoice->currency,
            'payment_method' => $validated['payment_method'] ?? null,
            'reference' => $validated['reference'] ?? null,
        ]);
        $newPaid = $invoice->payments()->sum('amount') + $amount;
        $invoice->update([
            'status' => $newPaid >= (float) $invoice->total ? 'paid' : 'partial',
        ]);
        if ($newPaid >= (float) $invoice->total) {
            \App\Jobs\FireWebhookJob::dispatch($companyId, 'invoice.paid', ['invoice_id' => $invoice->id, 'total' => $invoice->total]);
        }
        return response()->json(['data' => $payment], 201);
    }

    public function paymentLink(Request $request, Invoice $invoice): JsonResponse
    {
        $idemKey = $request->header('Idempotency-Key');
        if ($idemKey && strlen($idemKey) <= 128) {
            $cacheKey = 'payment_link:' . $invoice->id . ':' . $idemKey;
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return response()->json($cached['body'], $cached['status']);
            }
        }

        $secret = config('services.stripe.secret');
        if (!$secret) {
            return response()->json(['message' => 'Stripe is not configured'], 503);
        }
        if ($invoice->status === 'paid' || $invoice->status === 'cancelled') {
            return response()->json(['message' => 'Invoice is not open for payment'], 422);
        }
        $balanceDue = $invoice->balance_due ?? (float) $invoice->total - (float) ($invoice->payments()->sum('amount'));
        if ($balanceDue <= 0) {
            return response()->json(['message' => 'No balance due'], 422);
        }
        Stripe::setApiKey($secret);
        $currency = strtolower($invoice->currency ?? 'usd');
        $amountCents = (int) round($balanceDue * 100);
        if ($amountCents < 1) {
            return response()->json(['message' => 'Amount too small'], 422);
        }
        $successUrl = config('app.url') . '/invoices?stripe=success';
        $cancelUrl = config('app.url') . '/invoices?stripe=cancel';
        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => 'Invoice ' . $invoice->invoice_number,
                        'description' => 'Balance due',
                    ],
                    'unit_amount' => $amountCents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => 'invoice:' . $invoice->id,
            'metadata' => ['invoice_id' => (string) $invoice->id],
        ]);
        $body = ['data' => ['url' => $session->url]];
        $status = 200;
        if (isset($cacheKey)) {
            Cache::put($cacheKey, ['body' => $body, 'status' => $status], now()->addMinutes(60));
        }
        return response()->json($body, $status);
    }
}
