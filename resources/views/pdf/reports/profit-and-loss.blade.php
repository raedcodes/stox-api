@extends('pdf.reports.layout')

@section('content')
    <table class="totals" style="width: 400px;">
        <tr>
            <td class="label">Revenue</td>
            <td class="value">{{ $currency }} {{ number_format($revenue, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Cost of goods sold</td>
            <td class="value">-{{ $currency }} {{ number_format($cogs, 2) }}</td>
        </tr>
        <tr class="total">
            <td class="label">Gross profit</td>
            <td class="value">{{ $currency }} {{ number_format($grossProfit, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Gross margin</td>
            <td class="value">{{ number_format($grossMarginPercent, 1) }}%</td>
        </tr>
    </table>

    <p class="muted" style="margin-top: 20px;">
        This is a gross profit figure (revenue minus cost of goods sold). It does not include operating expenses,
        which are not tracked in the system.
    </p>
@endsection
