<?php

namespace App\Listeners;

use App\Events\InvoiceOverdueReminder;
use App\Mail\InvoiceOverdueMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendInvoiceOverdueReminder implements ShouldQueue
{
    public function handle(InvoiceOverdueReminder $event): void
    {
        $invoice = $event->invoice;
        $customer = $invoice->customer;
        if (!$customer || !$customer->email) {
            return;
        }
        try {
            Mail::to($customer->email)->send(new InvoiceOverdueMail($invoice, $event->beforeDue));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
