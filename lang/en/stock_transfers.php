<?php

return [

    'model_label' => 'Stock Transfer',
    'plural_model_label' => 'Stock Transfers',
    'section_title' => 'Transfer details',
    'items_section_title' => 'Items',
    'mark_in_transit_description' => 'This deducts stock from the source location now. The destination only receives it once you confirm arrival.',

    'fields' => [
        'from_location' => 'From location',
        'to_location' => 'To location',
        'items_to_receive' => 'Items to receive',
        'remaining' => 'Remaining',
        'receive_now' => 'Receive now',
    ],

    'columns' => [
        'transfer_number' => 'Transfer #',
        'from' => 'From',
        'to' => 'To',
    ],

    'actions' => [
        'submit_for_approval' => 'Submit for Approval',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'mark_in_transit' => 'Mark In Transit',
        'cancel_transfer' => 'Cancel Transfer',
        'receive' => 'Receive',
    ],

    'notifications' => [
        'submitted' => 'Submitted for approval',
        'cannot_submit' => 'Cannot submit',
        'approved' => 'Transfer approved',
        'cannot_approve' => 'Cannot approve',
        'rejected' => 'Transfer rejected',
        'cannot_reject' => 'Cannot reject',
        'marked_in_transit' => 'Transfer marked in transit',
        'cannot_mark_in_transit' => 'Cannot mark in transit',
        'cancelled' => 'Transfer cancelled',
        'cannot_cancel' => 'Cannot cancel',
        'nothing_to_receive' => 'Nothing to receive',
        'received' => 'Transfer received',
        'cannot_receive' => 'Cannot receive transfer',
    ],

];
