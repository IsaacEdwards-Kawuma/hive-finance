@extends('reports.pdf-layout')

@section('content')
<div class="section-title">Summary by aging bucket</div>
<table>
    <thead><tr><th>Current</th><th>1–30 days</th><th>31–60 days</th><th>61–90 days</th><th>90+ days</th><th class="text-right">Total</th></tr></thead>
    <tbody>
        <tr>
            <td>{{ number_format($buckets['current'] ?? 0, 2) }}</td>
            <td>{{ number_format($buckets['1-30'] ?? 0, 2) }}</td>
            <td>{{ number_format($buckets['31-60'] ?? 0, 2) }}</td>
            <td>{{ number_format($buckets['61-90'] ?? 0, 2) }}</td>
            <td>{{ number_format($buckets['90+'] ?? 0, 2) }}</td>
            <td class="text-right">{{ number_format(array_sum($buckets), 2) }}</td>
        </tr>
    </tbody>
</table>
<div class="section-title">By customer</div>
@foreach($by_customer as $cust)
<table>
    <thead><tr><th colspan="3">{{ $cust['customer'] }}</th><th class="text-right">Total: {{ number_format($cust['total'], 2) }}</th></tr><tr><th>Invoice #</th><th>Due date</th><th class="text-right">Balance</th><th></th></tr></thead>
    <tbody>
        @foreach($cust['invoices'] as $inv)
        <tr><td>{{ $inv['number'] }}</td><td>{{ $inv['due_date'] }}</td><td class="text-right">{{ number_format($inv['balance'], 2) }}</td><td></td></tr>
        @endforeach
    </tbody>
</table>
@endforeach
@endsection
