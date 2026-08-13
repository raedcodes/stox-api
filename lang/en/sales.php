<?php

return [

    'model_label' => 'Sale',
    'plural_model_label' => 'Sales',
    'section_title' => 'Sale details',
    'totals_section_title' => 'Totals',
    'items_section_title' => 'Items',
    'status_helper' => 'Use the "Complete Sale" action to finalize a sale — this deducts stock and updates the customer ledger.',
    'tax_rate_helper' => "This is the business-wide tax rate set in Tax Settings — it applies automatically to every sale and can't be changed per invoice.",
    'cancel_sale_description' => "This restocks everything on the invoice and reverses any charge to the customer's account. This cannot be undone.",

    'fields' => [
        'tax_rate' => 'Tax rate',
        'invoice_discount_type' => 'Invoice discount type',
        'invoice_discount_value' => 'Invoice discount value',
        'invoice_discount' => 'Invoice discount',
    ],

    'actions' => [
        'complete_sale' => 'Complete Sale',
        'print_invoice' => 'Print Invoice',
        'cancel_sale' => 'Cancel Sale',
    ],

    'notifications' => [
        'completed' => 'Sale completed',
        'cannot_complete' => 'Cannot complete sale',
        'cancelled' => 'Sale cancelled',
        'cannot_cancel' => 'Cannot cancel sale',
    ],

];
