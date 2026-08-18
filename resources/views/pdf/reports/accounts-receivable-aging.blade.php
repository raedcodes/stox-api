@extends('pdf.reports.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th class="num">Current</th>
                <th class="num">1-30 days</th>
                <th class="num">31-60 days</th>
                <th class="num">61-90 days</th>
                <th class="num">Over 90 days</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['customer']->name }}</td>
                    <td class="num">{{ $currency }} {{ number_format($row['buckets']['current'], 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($row['buckets']['1_30'], 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($row['buckets']['31_60'], 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($row['buckets']['61_90'], 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($row['buckets']['over_90'], 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($row['buckets']['total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Current</td>
            <td class="value">{{ $currency }} {{ number_format($totals['current'], 2) }}</td>
        </tr>
        <tr>
            <td class="label">1-30 days</td>
            <td class="value">{{ $currency }} {{ number_format($totals['1_30'], 2) }}</td>
        </tr>
        <tr>
            <td class="label">31-60 days</td>
            <td class="value">{{ $currency }} {{ number_format($totals['31_60'], 2) }}</td>
        </tr>
        <tr>
            <td class="label">61-90 days</td>
            <td class="value">{{ $currency }} {{ number_format($totals['61_90'], 2) }}</td>
        </tr>
        <tr>
            <td class="label">Over 90 days</td>
            <td class="value">{{ $currency }} {{ number_format($totals['over_90'], 2) }}</td>
        </tr>
        <tr class="total">
            <td class="label">Total outstanding</td>
            <td class="value">{{ $currency }} {{ number_format($totals['total'], 2) }}</td>
        </tr>
    </table>
@endsection
