<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = config('services.stripe.webhook_secret');
        if (!$secret) {
            return response('Webhook secret not configured', 500);
        }

        $sig = $request->header('Stripe-Signature');
        if (!$sig) {
            return response('Missing Stripe-Signature', 400);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $sig,
                $secret
            );
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        if ($event->type !== 'checkout.session.completed') {
            return response('OK', 200);
        }

        $session = $event->data->object;
        $invoiceId = $session->metadata->invoice_id ?? $session->client_reference_id ?? null;
        if ($invoiceId && str_starts_with((string) $invoiceId, 'invoice:')) {
            $invoiceId = substr($invoiceId, 8);
        }
        if (!$invoiceId) {
            return response('OK', 200);
        }

        $invoice = Invoice::withoutGlobalScope(\App\Scopes\CompanyScope::class)->find($invoiceId);
        if (!$invoice || $invoice->status === 'paid') {
            return response('OK', 200);
        }

        $amountTotal = isset($session->amount_total) ? ($session->amount_total / 100) : (float) $invoice->balance_due;
        $sessionId = $session->id ?? '';

        $existing = Payment::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('payable_type', Invoice::class)
            ->where('payable_id', $invoice->id)
            ->where('reference', $sessionId)
            ->exists();
        if ($existing) {
            return response('OK', 200);
        }

        Payment::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create([
            'company_id' => $invoice->company_id,
            'payable_type' => Invoice::class,
            'payable_id' => $invoice->id,
            'bank_account_id' => null,
            'paid_at' => now()->toDateString(),
            'amount' => $amountTotal,
            'currency' => $invoice->currency ?? 'USD',
            'payment_method' => 'stripe',
            'reference' => $sessionId,
        ]);

        $newPaid = $invoice->payments()->sum('amount') + $amountTotal;
        $invoice->update([
            'status' => $newPaid >= (float) $invoice->total ? 'paid' : 'partial',
        ]);

        if ($newPaid >= (float) $invoice->total) {
            \App\Jobs\FireWebhookJob::dispatch($invoice->company_id, 'invoice.paid', ['invoice_id' => $invoice->id, 'total' => $invoice->total]);
        }

        return response('OK', 200);
    }
}
