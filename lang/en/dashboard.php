<?php

return [

    'trend' => [
        'same_as' => 'Same as :label',
        'up_from_zero' => 'Up from zero :label',
        'vs' => ':percent% :direction vs. :label',
        'up' => 'up',
        'down' => 'down',
        'yesterday' => 'yesterday',
        'last_month' => 'last month',
    ],

    'kpis' => [
        'todays_sales' => "Today's sales",
        'todays_revenue' => "Today's revenue",
        'low_out_of_stock' => 'Low / out of stock',
        'sales_this_month' => 'Sales this month',
        'revenue_this_month' => 'Revenue this month',
        'gross_profit_this_month' => 'Gross profit this month',
        'margin' => ':percent% margin',
        'accounts_receivable' => 'Accounts receivable',
        'owed_by_customers' => 'Owed by customers',
        'accounts_payable' => 'Accounts payable',
        'owed_to_suppliers' => 'Owed to suppliers',
        'inventory_value' => 'Inventory value',
        'inventory_value_description' => 'Current stock, at cost',
    ],

    'widgets' => [
        'low_stock_heading' => 'Low & out-of-stock products',
        'expiry_alerts_heading' => 'Expiring & expired batches',
        'recent_credit_sales_heading' => 'Recent credit sales',
        'top_products_heading' => 'Top products (last 30 days)',
        'revenue_trend_heading' => 'Revenue vs. cost of goods (last 30 days)',
        'top_products_chart_heading' => 'Top products by revenue (last 30 days)',
        'stock_expiry_timeline_heading' => 'Stock expiry timeline',
    ],

    'columns' => [
        'on_hand' => 'On hand',
        'units_sold' => 'Units sold',
        'gross_revenue' => 'Gross revenue',
        'customer_owes_total' => 'Customer owes (total)',
        'amount' => 'Amount',
    ],

    'actions' => [
        'create_po' => 'Create PO',
    ],

    'chart' => [
        'revenue' => 'Revenue',
        'cost_of_goods_sold' => 'Cost of goods sold',
        'quantity' => 'Quantity',
        'already_expired' => 'Already expired',
        'within_30_days' => '0–30 days',
        'within_60_days' => '31–60 days',
        'within_90_days' => '61–90 days',
        'beyond_90_days' => '90+ days',
    ],

    'working_from' => 'Working from',
    'all_locations' => 'All Locations',
    'location_helper' => 'Scopes this dashboard and defaults into POS, purchase orders, and transfers — still changeable per form.',

];
