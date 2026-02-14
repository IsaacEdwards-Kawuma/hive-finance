@extends('reports.pdf-layout')

@section('content')
<table>
    <thead><tr><th>Item</th><th class="text-right">Amount</th></tr></thead>
    <tbody>
        <tr><td>Income (operating)</td><td class="text-right">{{ number_format($operating_activities['income'], 2) }}</td></tr>
        <tr><td>Expenses (operating)</td><td class="text-right">({{ number_format($operating_activities['expense'], 2) }})</td></tr>
        <tr class="subtotal-row"><td>Net operating activities</td><td class="text-right">{{ number_format($operating_activities['net'], 2) }}</td></tr>
        <tr><td>Bank / cash net change</td><td class="text-right">{{ number_format($bank_net_change, 2) }}</td></tr>
        <tr class="total-row"><td>Net cash change</td><td class="text-right">{{ number_format($net_cash_change, 2) }}</td></tr>
    </tbody>
</table>
@endsection
