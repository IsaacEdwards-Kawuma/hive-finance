<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>body { font-family: sans-serif; line-height: 1.6; color: #333; } .button { display: inline-block; padding: 10px 20px; background: #1e293b; color: #fff !important; text-decoration: none; border-radius: 6px; margin-top: 16px; }</style>
</head>
<body>
    <p>Hello {{ $invoice->customer->name ?? 'Customer' }},</p>
    <p>This is a reminder that invoice <strong>{{ $invoice->invoice_number }}</strong> (due {{ $invoice->due_date?->format('M j, Y') }}) has an outstanding balance of <strong>{{ number_format($invoice->balance_due, 2) }} {{ $invoice->currency }}</strong>.</p>
    @if(!empty($beforeDue))
    <p>Please arrange payment before the due date.</p>
    @else
    <p>Please arrange payment at your earliest convenience.</p>
    @endif
    <p>Thank you.</p>
</body>
</html>
