<?php

return [

    'navigation_label' => 'Inventory Valuation Report',
    'unit_cost_tooltip' => "Weighted average cost across this product's batches at this location — batches can have different actual costs (e.g. landed costs vary by shipment).",

    'fields' => [
        'unit_cost_avg' => 'Unit cost (avg)',
        'total_value' => 'Total Value',
    ],

    'kpis' => [
        'total_stock_value' => 'Total Stock Value',
        'products_tracked' => 'Products Tracked',
        'total_units_on_hand' => 'Total Units On Hand',
    ],

];
