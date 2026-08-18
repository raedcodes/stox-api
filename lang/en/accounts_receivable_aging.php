<?php

return [

    'navigation_label' => 'Accounts Receivable Aging',
    'empty_state' => 'No outstanding customer balances.',

    'buckets' => [
        'current' => 'Current',
        '1_30' => '1-30 days',
        '31_60' => '31-60 days',
        '61_90' => '61-90 days',
        'over_90' => 'Over 90 days',
    ],

    'kpis' => [
        'total_outstanding' => 'Total outstanding',
        'overdue' => 'Overdue',
        'severely_overdue' => 'Over 90 days overdue',
    ],

];
