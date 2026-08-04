@extends('pdf.reports.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Description</th>
                <th>Location</th>
                <th class="num">On hand</th>
                <th class="num">Unit cost (avg)</th>
                <th class="num">Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row->product->name }}</td>
                    <td>{{ $row->product->description ?? '—' }}</td>
                    <td>{{ $row->location->name }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($row->total_quantity, 2), '0'), '.') }}</td>
                    <td class="num">{{ $currency }} {{ number_format($row->total_quantity > 0 ? $row->total_value / $row->total_quantity : 0, 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($row->total_value, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr class="total">
            <td class="label">Total value</td>
            <td class="value">{{ $currency }} {{ number_format($rows->sum('total_value'), 2) }}</td>
        </tr>
    </table>
@endsection
