@extends('reports.pdf-layout')

@section('content')
<table>
    <thead><tr><th>Item</th><th class="text-right">Amount</th></tr></thead>
    <tbody>
        <tr><td>Sales tax collected</td><td class="text-right">{{ number_format($sales_tax_collected, 2) }}</td></tr>
        <tr><td>Purchase tax paid</td><td class="text-right">{{ number_format($purchase_tax_paid, 2) }}</td></tr>
    </tbody>
</table>
@if($tax_rates->isNotEmpty())
<div class="section-title">Tax rates</div>
<table>
    <thead><tr><th>Name</th><th class="text-right">Rate %</th></tr></thead>
    <tbody>
        @foreach($tax_rates as $r)
        <tr><td>{{ $r->name }}</td><td class="text-right">{{ number_format($r->rate, 2) }}%</td></tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
