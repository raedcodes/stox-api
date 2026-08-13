<?php

return [

    'navigation_label' => 'Cash Drawer',
    'model_label' => 'Cash Session',
    'plural_model_label' => 'Cash Sessions',

    'fields' => [
        'opened' => 'Opened',
        'opened_by' => 'Opened by',
        'opening_float' => 'Opening float',
        'closed' => 'Closed',
        'expected' => 'Expected',
        'counted' => 'Counted',
        'variance' => 'Variance',
        'expected_cash_now' => 'Expected cash right now',
        'counted_cash' => 'Counted cash',
    ],

    'actions' => [
        'open_till' => 'Open Till',
        'close_till' => 'Close Till',
    ],

    'notifications' => [
        'opened' => 'Till opened',
        'cannot_open' => 'Cannot open till',
        'closed' => 'Till closed',
        'cannot_close' => 'Cannot close till',
    ],

    'opening_float_helper' => 'The cash already in the drawer before any sales are rung up.',
    'notes_helper' => 'Explain any discrepancy between counted and expected cash, if there is one.',

];
