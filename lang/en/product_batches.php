<?php

return [

    'model_label' => 'Product Batch',
    'plural_model_label' => 'Product Batches',
    'section_title' => 'Batch details',
    'no_expiry' => 'No expiry',
    'unit_cost_helper' => "Defaults to the product's cost price — override if this batch's actual cost differs (e.g. landed costs).",
    'expiry_date_helper' => "Leave empty if this batch doesn't expire.",
    'is_active_helper' => "Turn off for a batch that's been written off, recalled, or fully used.",

    'fields' => [
        'batch_number' => 'Batch / lot number',
        'unit_cost' => 'Unit cost',
        'manufactured_date' => 'Manufactured date',
        'expiry_date' => 'Expiry date',
    ],

    'columns' => [
        'batch_hash' => 'Batch #',
        'manufactured' => 'Manufactured',
    ],

    'filters' => [
        'expired' => 'Expired',
        'expiring_soon' => 'Expiring within 30 days',
    ],

];
