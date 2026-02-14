@extends('reports.pdf-layout')

@section('content')
<div class="section-title">Assets</div>
<table>
    <thead><tr><th>Account</th><th class="text-right">Balance</th></tr></thead>
    <tbody>
        @foreach($assets as $row)
        <tr><td>{{ $row->code }} – {{ $row->name }}</td><td class="text-right">{{ number_format($row->balance, 2) }}</td></tr>
        @endforeach
        <tr class="total-row"><td>Total Assets</td><td class="text-right">{{ number_format($total_assets, 2) }}</td></tr>
    </tbody>
</table>
<div class="section-title">Liabilities</div>
<table>
    <thead><tr><th>Account</th><th class="text-right">Balance</th></tr></thead>
    <tbody>
        @foreach($liabilities as $row)
        <tr><td>{{ $row->code }} – {{ $row->name }}</td><td class="text-right">{{ number_format($row->balance, 2) }}</td></tr>
        @endforeach
        <tr class="total-row"><td>Total Liabilities</td><td class="text-right">{{ number_format($total_liabilities, 2) }}</td></tr>
    </tbody>
</table>
<div class="section-title">Equity</div>
<table>
    <thead><tr><th>Account</th><th class="text-right">Balance</th></tr></thead>
    <tbody>
        @foreach($equity as $row)
        <tr><td>{{ $row->code }} – {{ $row->name }}</td><td class="text-right">{{ number_format($row->balance, 2) }}</td></tr>
        @endforeach
        <tr class="total-row"><td>Total Equity</td><td class="text-right">{{ number_format($total_equity, 2) }}</td></tr>
    </tbody>
</table>
<table>
    <tr class="total-row"><td>Total Liabilities &amp; Equity</td><td class="text-right">{{ number_format($total_liabilities + $total_equity, 2) }}</td></tr>
</table>
@endsection
