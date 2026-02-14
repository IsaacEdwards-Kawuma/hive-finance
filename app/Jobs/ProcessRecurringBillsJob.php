<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Models\RecurringBill;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessRecurringBillsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today()->toDateString();
        RecurringBill::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('enabled', true)
            ->where('next_run_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->each(function (RecurringBill $recurring) {
                $this->createBillFromRecurring($recurring);
                $this->advanceNextRun($recurring);
            });
    }

    private function createBillFromRecurring(RecurringBill $recurring): void
    {
        $template = $recurring->template ?? [];
        $items = $template['items'] ?? [['description' => 'Recurring bill', 'quantity' => 1, 'price' => 0, 'tax' => 0, 'discount' => 0]];
        DB::transaction(function () use ($recurring, $items) {
            $companyId = $recurring->company_id;
            $lastNum = Bill::withoutGlobalScope(\App\Scopes\CompanyScope::class)->where('company_id', $companyId)->max('id');
            $billDate = $recurring->next_run_date->toDateString();
            $dueDate = $recurring->next_run_date->addDays(30)->toDateString();
            $bill = Bill::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create([
                'company_id' => $companyId,
                'vendor_id' => $recurring->vendor_id,
                'bill_number' => 'BILL-' . (($lastNum ?? 0) + 1),
                'bill_date' => $billDate,
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
                $bill->items()->create([
                    'description' => $item['description'] ?? 'Line',
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
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
        });
    }

    private function advanceNextRun(RecurringBill $recurring): void
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
