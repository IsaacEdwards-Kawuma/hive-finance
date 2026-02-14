@extends('reports.pdf-layout')

@section('content')
@if($vendor ?? null)
<p><strong>Vendor:</strong> {{ $vendor->name }}@if($vendor->email ?? null) &nbsp;|&nbsp; {{ $vendor->email }}@endif</p>
@endif
<table>
    <thead><tr><th>Date</th><th>Type</th><th>Number</th><th class="text-right">Amount</th><th class="text-right">Balance</th></tr></thead>
    <tbody>
        @foreach($lines as $line)
        <tr>
            <td>{{ $line['date'] }}</td>
            <td>{{ ucfirst($line['type']) }}</td>
            <td>{{ $line['number'] }}</td>
            <td class="text-right">{{ number_format($line['amount'], 2) }}</td>
            <td class="text-right">{{ isset($line['balance']) ? number_format($line['balance'], 2) : '–' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
