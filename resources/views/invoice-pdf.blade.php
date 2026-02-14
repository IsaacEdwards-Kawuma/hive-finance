<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; color: #1e293b; }
        .header { display: flex; justify-content: space-between; margin-bottom: 32px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; font-size: 1.1em; }
        .meta { color: #64748b; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 style="margin:0">Invoice</h1>
            <p class="meta">{{ $invoice->invoice_number }}</p>
        </div>
        <div class="text-right">
            @if($company ?? null)
                <strong>{{ $company->name }}</strong>
            @endif
        </div>
    </div>
    <p><strong>Bill to:</strong><br>
        {{ $invoice->customer->name ?? '—' }}<br>
        @if($invoice->customer->email){{ $invoice->customer->email }}<br>@endif
        @if($invoice->customer->phone){{ $invoice->customer->phone }}@endif
    </p>
    <p class="meta">Date: {{ $invoice->invoice_date?->format('Y-m-d') }} &nbsp; Due: {{ $invoice->due_date?->format('Y-m-d') }} &nbsp; Status: {{ $invoice->status }}</p>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items ?? [] as $line)
            <tr>
                <td>{{ $line->description }}</td>
                <td class="text-right">{{ number_format($line->quantity, 2) }}</td>
                <td class="text-right">{{ number_format($line->price, 2) }}</td>
                <td class="text-right">{{ number_format($line->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <table style="max-width: 300px; margin-left: auto;">
        <tr><td>Subtotal</td><td class="text-right">{{ number_format($invoice->subtotal ?? 0, 2) }}</td></tr>
        <tr><td>Tax</td><td class="text-right">{{ number_format($invoice->tax_total ?? 0, 2) }}</td></tr>
        <tr><td>Discount</td><td class="text-right">-{{ number_format($invoice->discount_total ?? 0, 2) }}</td></tr>
        <tr class="total-row"><td>Total</td><td class="text-right">{{ number_format($invoice->total ?? 0, 2) }} {{ $invoice->currency ?? 'USD' }}</td></tr>
    </table>
    @if($invoice->notes)
    <p class="meta mt-4"><strong>Notes:</strong> {{ $invoice->notes }}</p>
    @endif
</body>
</html>
