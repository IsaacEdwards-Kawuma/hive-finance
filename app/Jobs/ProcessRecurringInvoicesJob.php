<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\RecurringInvoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessRecurringInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today()->toDateString();
        RecurringInvoice::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('enabled', true)
            ->where('next_run_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->each(function (RecurringInvoice $recurring) {
                $this->createInvoiceFromRecurring($recurring);
                $this->advanceNextRun($recurring);
            });
    }

    private function createInvoiceFromRecurring(RecurringInvoice $recurring): void
    {
        $template = $recurring->template ?? [];
        $items = $template['items'] ?? [['description' => 'Recurring charge', 'quantity' => 1, 'price' => 0, 'tax' => 0, 'discount' => 0]];
        DB::transaction(function () use ($recurring, $items) {
            $companyId = $recurring->company_id;
            $lastNum = Invoice::withoutGlobalScope(\App\Scopes\CompanyScope::class)->where('company_id', $companyId)->max('id');
            $invoiceDate = $recurring->next_run_date->toDateString();
            $dueDate = $recurring->next_run_date->addDays(30)->toDateString();
            $invoice = Invoice::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create([
                'company_id' => $companyId,
                'customer_id' => $recurring->customer_id,
                'invoice_number' => 'INV-' . (($lastNum ?? 0) + 1),
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'currency' => $template['currency'] ?? 'USD',
                'exchange_rate' => 1,
                'status' => 'draft',
                'notes' => $template['notes'] ?? null,
            ]);
            $subtotal = $taxTotal = $discountTotal = 0;
            foreach ($items as $i => $item) {
                $total = ($item['quantity'] ?? 1) * ($item['price'] ?? 0) + ($item['tax'] ?? 0) - ($item['discount'] ?? 0);
                $subtotal += ($item['quantity'] ?? 1) * ($item['price'] ?? 0);
                $taxTotal += $item['tax'] ?? 0;
                $discountTotal += $item['discount'] ?? 0;
                $invoice->items()->create([
                    'description' => $item['description'] ?? 'Line',
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
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
        });
    }

    private function advanceNextRun(RecurringInvoice $recurring): void
    {
        $date = Carbon::parse($recurring->next_run_date);
        $next = match ($recurring->frequency) {
            'daily' => $date->copy()->addDay(),
            'weekly' => $date->copy()->addWeek(),
            'monthly' => $date->copy()->addMonth(),
            'yearly' => $date->copy()->addYear(),
            default => $date->copy()->addMonth(),
        };
        $recurring->update(['next_run_date' => $next->toDateString()]);
    }
}
