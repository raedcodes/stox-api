@extends('pdf.reports.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice #</th>
                <th>Product</th>
                <th>Category</th>
                <th>Cashier</th>
                <th class="num">Qty</th>
                <th class="num">Line total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->sale->sale_date->format('Y-m-d') }}</td>
                    <td>{{ $item->sale->sale_number }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->product->category?->name ?? '—' }}</td>
                    <td>{{ $item->sale->cashier?->name ?? '—' }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                    <td class="num">{{ $currency }} {{ number_format($item->total(), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr class="total">
            <td class="label">Total</td>
            <td class="value">{{ $currency }} {{ number_format($items->sum(fn ($item) => $item->total()), 2) }}</td>
        </tr>
    </table>
@endsection
