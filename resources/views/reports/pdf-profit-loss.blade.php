@extends('reports.pdf-layout')

@section('content')
<div class="section-title">Income</div>
<table>
    <thead><tr><th>Account</th><th class="text-right">Amount</th></tr></thead>
    <tbody>
        @foreach($income as $row)
        <tr><td>{{ $row->code }} – {{ $row->name }}</td><td class="text-right">{{ number_format($row->balance, 2) }}</td></tr>
        @endforeach
        <tr class="subtotal-row"><td>Total Income</td><td class="text-right">{{ number_format($total_income, 2) }}</td></tr>
    </tbody>
</table>
<div class="section-title">Expenses</div>
<table>
    <thead><tr><th>Account</th><th class="text-right">Amount</th></tr></thead>
    <tbody>
        @foreach($expense as $row)
        <tr><td>{{ $row->code }} – {{ $row->name }}</td><td class="text-right">{{ number_format($row->balance, 2) }}</td></tr>
        @endforeach
        <tr class="subtotal-row"><td>Total Expenses</td><td class="text-right">{{ number_format($total_expense, 2) }}</td></tr>
    </tbody>
</table>
<table>
    <tr class="total-row"><td>Net Income</td><td class="text-right">{{ number_format($net_income, 2) }}</td></tr>
</table>
@endsection
