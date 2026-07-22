@extends('pdf.reports.layout')

@section('content')
    <p><strong>{{ $customer->name }}</strong></p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th class="num">Amount</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="4"><em>Opening balance</em></td>
                <td class="num">{{ $currency }} {{ number_format($openingBalance, 2) }}</td>
            </tr>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['transaction']->transaction_date->format('Y-m-d') }}</td>
                    <td>{{ $row['transaction']->type->getLabel() }}</td>
                    <td>{{ $row['transaction']->description ?? '—' }}</td>
                    <td class="num">{{ $currency }} {{ number_format($row['transaction']->amount, 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($row['running_balance'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr class="total">
            <td class="label">Closing balance</td>
            <td class="value">{{ $currency }} {{ number_format($closingBalance, 2) }}</td>
        </tr>
    </table>
@endsection
