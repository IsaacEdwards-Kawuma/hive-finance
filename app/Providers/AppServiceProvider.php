<?php

namespace App\Providers;

use App\Models\Bill;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Investment;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Events\InvoiceOverdueReminder;
use App\Listeners\SendInvoiceOverdueReminder;
use App\Observers\AuditObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \App\Services\ModuleLoader::load();
        Invoice::observe(AuditObserver::class);
        Bill::observe(AuditObserver::class);
        JournalEntry::observe(AuditObserver::class);
        Investment::observe(AuditObserver::class);
        Payment::observe(AuditObserver::class);
        CreditNote::observe(AuditObserver::class);
        Event::listen(InvoiceOverdueReminder::class, SendInvoiceOverdueReminder::class);
    }
}
