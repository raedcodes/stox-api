<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1f2937; }
        .header { margin-bottom: 16px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 5px 7px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f3f4f6; }
        td.num, th.num { text-align: right; }
        table.totals { width: 300px; margin-left: auto; margin-top: 12px; }
        table.totals td { padding: 3px 7px; }
        table.totals td.label { text-align: right; }
        table.totals td.value { text-align: right; width: 120px; }
        table.totals tr.total td { font-size: 13px; font-weight: bold; border-top: 2px solid #1f2937; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $tenant->name }}</h1>
        @if ($tenant->address)
            <div class="muted">{{ $tenant->address }}</div>
        @endif
        <h1 style="margin-top: 12px;">{{ $title }}</h1>
        @if (!empty($subtitle))
            <div class="muted">{{ $subtitle }}</div>
        @endif
    </div>

    @yield('content')
</body>
</html>
