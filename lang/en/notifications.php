<?php

return [
    'daily_digest' => [
        'subject' => 'Your daily operations digest',
        'greeting' => "Here's what needs your attention today.",
        'low_stock_heading' => ':count product(s) are low on stock:',
        'expiring_heading' => ':count batch(es) are expiring soon:',
        'action' => 'View stock levels',
    ],

    'purchase_order_approval' => [
        'subject' => 'Purchase order awaiting approval',
        'greeting' => 'A purchase order needs your approval.',
        'line' => ':po_number to :supplier for :total is waiting for approval.',
        'action' => 'Review purchase order',
    ],
];
