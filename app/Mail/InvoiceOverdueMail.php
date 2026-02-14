<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceOverdueMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice, public bool $beforeDue = false) {}

    public function envelope(): Envelope
    {
        $subject = $this->beforeDue
            ? 'Reminder: Invoice ' . $this->invoice->invoice_number . ' is due soon'
            : 'Reminder: Invoice ' . $this->invoice->invoice_number . ' is overdue';
        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-overdue',
        );
    }
}
