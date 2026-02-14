<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceOverdueReminder
{
    use Dispatchable, SerializesModels;

    /** @param bool $beforeDue True when reminder is sent X days before due (vs after due). */
    public function __construct(public Invoice $invoice, public bool $beforeDue = false) {}
}
