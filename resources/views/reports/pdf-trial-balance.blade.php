@extends('reports.pdf-layout')

@section('content')
<table>
    <thead><tr><th>Account</th><th>Code</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['code'] }}</td>
            <td class="text-right">{{ $row['debit'] != 0 ? number_format($row['debit'], 2) : '' }}</td>
            <td class="text-right">{{ $row['credit'] != 0 ? number_format($row['credit'], 2) : '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
