@extends('pdf.reports.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>PO #</th>
                <th>Supplier</th>
                <th>Status</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->order_date->format('Y-m-d') }}</td>
                    <td>{{ $order->po_number }}</td>
                    <td>{{ $order->supplier->name }}</td>
                    <td>{{ $order->status->getLabel() }}</td>
                    <td class="num">{{ $currency }} {{ number_format($order->total(), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr class="total">
            <td class="label">Total</td>
            <td class="value">{{ $currency }} {{ number_format($orders->sum(fn ($order) => $order->total()), 2) }}</td>
        </tr>
    </table>
@endsection
