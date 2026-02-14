<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today();
        $todayStr = $today->toDateString();
        $companies = Company::all()->keyBy('id');

        // Overdue: due_date <= today
        Invoice::with('customer')
            ->whereIn('status', ['sent', 'partial'])
            ->whereDate('due_date', '<=', $todayStr)
            ->where('due_date', '>=', $today->copy()->subDays(90)->toDateString())
            ->each(function (Invoice $invoice) use ($today, $companies) {
                if ($invoice->balance_due <= 0) {
                    return;
                }
                $company = $companies->get($invoice->company_id);
                $settings = $company?->settings ?? [];
                if (isset($settings['payment_reminders_enabled']) && !$settings['payment_reminders_enabled']) {
                    return;
                }
                $daysAfter = $settings['payment_reminders_days_after'] ?? '0, 7, 30';
                $daysList = array_map('intval', array_filter(preg_split('/[\s,]+/', (string) $daysAfter)));
                if ($daysList === []) {
                    $daysList = [0];
                }
                $dueDate = Carbon::parse($invoice->due_date);
                $daysOverdue = (int) $dueDate->diffInDays($today, false);
                if (!in_array($daysOverdue, $daysList, true)) {
                    return;
                }
                event(new \App\Events\InvoiceOverdueReminder($invoice, false));
            });

        // Before due: due_date > today, send when today is exactly X days before due
        $daysBeforeSetting = null;
        Invoice::with('customer')
            ->whereIn('status', ['sent', 'partial'])
            ->whereDate('due_date', '>', $todayStr)
            ->each(function (Invoice $invoice) use ($today, $companies) {
                if ($invoice->balance_due <= 0) {
                    return;
                }
                $company = $companies->get($invoice->company_id);
                $settings = $company?->settings ?? [];
                if (isset($settings['payment_reminders_enabled']) && !$settings['payment_reminders_enabled']) {
                    return;
                }
                $daysBeforeRaw = $settings['payment_reminders_days_before'] ?? '';
                if ($daysBeforeRaw === '' || $daysBeforeRaw === null) {
                    return;
                }
                $daysBeforeList = array_map('intval', array_filter(preg_split('/[\s,]+/', (string) $daysBeforeRaw)));
                if ($daysBeforeList === []) {
                    return;
                }
                $dueDate = Carbon::parse($invoice->due_date);
                $daysUntilDue = (int) $today->diffInDays($dueDate, false);
                if (!in_array($daysUntilDue, $daysBeforeList, true)) {
                    return;
                }
                event(new \App\Events\InvoiceOverdueReminder($invoice, true));
            });
    }
}
