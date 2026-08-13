<?php

return [

    'model_label' => 'Purchase Order',
    'plural_model_label' => 'Purchase Orders',
    'section_title' => 'Purchase order details',
    'items_section_title' => 'Items',
    'landed_costs_section_title' => 'Landed costs',
    'totals_section_title' => 'Totals',
    'landed_costs_helper' => 'Freight, customs, handling, or any other cost added on top of the order value.',
    'approve_description' => 'This places the order with the supplier.',

    'fields' => [
        'deliver_to' => 'Deliver to',
        'expected_date' => 'Expected delivery date',
        'ordered' => 'Ordered',
        'received' => 'Received',
        'landed_costs' => 'Landed costs',
        'items_to_receive' => 'Items to receive',
        'remaining' => 'Remaining',
        'receive_now' => 'Receive now',
        'batch_number' => 'Batch #',
    ],

    'actions' => [
        'submit_for_approval' => 'Submit for Approval',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'cancel_order' => 'Cancel Order',
        'print_po' => 'Print PO',
        'receive_stock' => 'Receive Stock',
        'add_charge' => 'Add charge',
    ],

    'notifications' => [
        'submitted' => 'Submitted for approval',
        'cannot_submit' => 'Cannot submit',
        'approved' => 'Order approved and placed',
        'cannot_approve' => 'Cannot approve',
        'rejected' => 'Order rejected',
        'cannot_reject' => 'Cannot reject',
        'cancelled' => 'Order cancelled',
        'cannot_cancel' => 'Cannot cancel',
        'nothing_to_receive' => 'Nothing to receive',
        'received' => 'Stock received',
    ],

];
