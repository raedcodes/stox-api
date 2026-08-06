<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $purchaseOrder->po_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        .header { display: table; width: 100%; margin-bottom: 20px; }
        .header .business { display: table-cell; width: 60%; vertical-align: top; }
        .header .order { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
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
        <div class="order">
            <h1>PURCHASE ORDER</h1>
            <div><strong>{{ $purchaseOrder->po_number }}</strong></div>
            <div class="muted">Date: {{ $purchaseOrder->order_date->format('Y-m-d') }}</div>
            @if ($purchaseOrder->expected_date)
                <div class="muted">Expected: {{ $purchaseOrder->expected_date->format('Y-m-d') }}</div>
            @endif
            <div class="muted">Status: {{ $purchaseOrder->status->getLabel() }}</div>
        </div>
    </div>

    <div class="section-title">Supplier</div>
    <div>{{ $purchaseOrder->supplier->name }}</div>
    @if ($purchaseOrder->supplier->phone)
        <div class="muted">{{ $purchaseOrder->supplier->phone }}</div>
    @endif
    @if ($purchaseOrder->supplier->address)
        <div class="muted">{{ $purchaseOrder->supplier->address }}</div>
    @endif

    <div class="section-title">Deliver To</div>
    <div>{{ $purchaseOrder->location->name }}</div>

    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th class="num">Ordered</th>
                <th class="num">Received</th>
                <th class="num">Unit Cost</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseOrder->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($item->quantity_ordered, 2), '0'), '.') }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($item->quantity_received, 2), '0'), '.') }}</td>
                    <td class="num">{{ $currency }} {{ number_format($item->unit_cost, 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($item->quantity_ordered * $item->unit_cost, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($purchaseOrder->charges->isNotEmpty())
        <table class="items">
            <thead>
                <tr>
                    <th>Landed cost</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchaseOrder->charges as $charge)
                    <tr>
                        <td>{{ $charge->description }}</td>
                        <td class="num">{{ $currency }} {{ number_format($charge->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ $currency }} {{ number_format($purchaseOrder->subtotal(), 2) }}</td>
        </tr>
        @if ($purchaseOrder->landedCostTotal() > 0)
            <tr>
                <td class="label">Landed costs</td>
                <td class="value">{{ $currency }} {{ number_format($purchaseOrder->landedCostTotal(), 2) }}</td>
            </tr>
        @endif
        <tr class="total">
            <td class="label">Total</td>
            <td class="value">{{ $currency }} {{ number_format($purchaseOrder->total(), 2) }}</td>
        </tr>
    </table>

    @if ($purchaseOrder->notes)
        <div class="section-title">Notes</div>
        <div>{{ $purchaseOrder->notes }}</div>
    @endif
</body>
</html>
