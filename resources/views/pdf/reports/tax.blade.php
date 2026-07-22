@extends('pdf.reports.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice #</th>
                <th>Product</th>
                <th class="num">Taxable subtotal</th>
                <th class="num">Tax collected</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->sale->sale_date->format('Y-m-d') }}</td>
                    <td>{{ $item->sale->sale_number }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td class="num">{{ $currency }} {{ number_format($item->subtotal(), 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($item->taxAmount(), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Total taxable subtotal</td>
            <td class="value">{{ $currency }} {{ number_format($items->sum(fn ($i) => $i->subtotal()), 2) }}</td>
        </tr>
        <tr class="total">
            <td class="label">Total tax collected</td>
            <td class="value">{{ $currency }} {{ number_format($items->sum(fn ($i) => $i->taxAmount()), 2) }}</td>
        </tr>
    </table>
@endsection
