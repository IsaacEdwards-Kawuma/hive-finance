@extends('reports.pdf-layout')

@section('content')
@if($account ?? null)
<p class="section-title">Account: {{ $account->code }} – {{ $account->name }}</p>
@endif
<table>
    <thead><tr><th>Date</th><th>Entry #</th><th>Description</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead>
    <tbody>
        @foreach($lines as $line)
        <tr>
            <td>{{ $line->journalEntry?->date?->format('Y-m-d') ?? '—' }}</td>
            <td>{{ $line->journalEntry?->number ?? '—' }}</td>
            <td>{{ $line->memo ?: ($line->journalEntry?->description ?? '—') }}</td>
            <td class="text-right">{{ $line->debit != 0 ? number_format($line->debit, 2) : '' }}</td>
            <td class="text-right">{{ $line->credit != 0 ? number_format($line->credit, 2) : '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
