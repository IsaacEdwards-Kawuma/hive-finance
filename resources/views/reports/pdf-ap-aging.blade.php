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
<div class="section-title">By vendor</div>
@foreach($by_vendor as $ven)
<table>
    <thead><tr><th colspan="3">{{ $ven['vendor'] }}</th><th class="text-right">Total: {{ number_format($ven['total'], 2) }}</th></tr><tr><th>Bill #</th><th>Due date</th><th class="text-right">Balance</th><th></th></tr></thead>
    <tbody>
        @foreach($ven['bills'] as $bill)
        <tr><td>{{ $bill['number'] }}</td><td>{{ $bill['due_date'] }}</td><td class="text-right">{{ number_format($bill['balance'], 2) }}</td><td></td></tr>
        @endforeach
    </tbody>
</table>
@endforeach
@endsection
