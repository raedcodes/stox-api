<?php

return [

    'model_label' => 'Customer',
    'plural_model_label' => 'Customers',
    'no_limit' => 'No limit',
    'default_suffix' => ':name (default)',
    'reference_helper' => 'e.g. receipt or reference number.',

    'fields' => [
        'customer_type' => 'Customer type',
        'credit_limit' => 'Credit limit',
        'credit_limit_helper' => 'Leave blank for no limit. Blocks a credit sale from being completed once it would push this customer over the limit.',
        'current_balance' => 'Current balance',
    ],

    'columns' => [
        'balance' => 'Balance',
    ],

    'actions' => [
        'record_payment' => 'Record Payment',
    ],

    'notifications' => [
        'payment_recorded' => 'Payment recorded',
        'exceeds_balance' => 'This exceeds the outstanding balance — the difference is now a credit on the account.',
    ],

];
