<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $sale->sale_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        .header { display: table; width: 100%; margin-bottom: 20px; }
        .header .business { display: table-cell; width: 60%; vertical-align: top; }
        .header .invoice { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
        .business-logo { max-height: 60px; margin-bottom: 8px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.items th, table.items td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        table.items th { background: #f3f4f6; }
        table.items td.num, table.items th.num { text-align: right; }
        table.totals { width: 100%; margin-top: 12px; }
        table.totals td { padding: 3px 8px; }
        table.totals td.label { text-align: right; }
        table.totals td.value { text-align: right; width: 120px; }
        table.totals tr.total td { font-size: 14px; font-weight: bold; border-top: 2px solid #1f2937; }
        .section-title { margin-top: 24px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="business">
            @if ($tenant->logo)
                <img class="business-logo" src="{{ public_path('storage/' . $tenant->logo) }}">
            @endif
            <h1>{{ $tenant->name }}</h1>
            @if ($tenant->address)
                <div class="muted">{{ $tenant->address }}</div>
            @endif
            @if ($tenant->tax_number)
                <div class="muted">Tax #: {{ $tenant->tax_number }}</div>
            @endif
        </div>
        <div class="invoice">
            <h1>INVOICE</h1>
            <div><strong>{{ $sale->sale_number }}</strong></div>
            <div class="muted">Date: {{ $sale->sale_date->format('Y-m-d') }}</div>
            <div class="muted">Payment: {{ $sale->payment_method?->getLabel() ?? '-' }}</div>
        </div>
    </div>

    <div class="section-title">Bill To</div>
    <div>{{ $sale->customer->name }}</div>
    @if ($sale->customer->phone)
        <div class="muted">{{ $sale->customer->phone }}</div>
    @endif
    @if ($sale->customer->address)
        <div class="muted">{{ $sale->customer->address }}</div>
    @endif

    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th class="num">Qty</th>
                <th class="num">Unit Price</th>
                <th class="num">Discount</th>
                <th class="num">Tax</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                    <td class="num">{{ $currency }} {{ number_format($item->unit_price, 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($item->discountAmount(), 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($item->taxAmount(), 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($item->total(), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ $currency }} {{ number_format($sale->subtotal(), 2) }}</td>
        </tr>
        <tr>
            <td class="label">Tax</td>
            <td class="value">{{ $currency }} {{ number_format($sale->taxTotal(), 2) }}</td>
        </tr>
        @if ($sale->invoiceDiscountAmount() > 0)
            <tr>
                <td class="label">Discount</td>
                <td class="value">-{{ $currency }} {{ number_format($sale->invoiceDiscountAmount(), 2) }}</td>
            </tr>
        @endif
        <tr class="total">
            <td class="label">Total</td>
            <td class="value">{{ $currency }} {{ number_format($sale->total(), 2) }}</td>
        </tr>
    </table>

    @if ($sale->notes)
        <div class="section-title">Notes</div>
        <div>{{ $sale->notes }}</div>
    @endif
</body>
</html>
