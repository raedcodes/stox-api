<?php

return [

    'user_role' => [
        'super-admin' => 'Super Admin',
        'owner' => 'Owner',
        'manager' => 'Manager',
        'cashier' => 'Cashier',
        'storekeeper' => 'Storekeeper',
    ],

    'location_type' => [
        'warehouse' => 'Warehouse',
        'store' => 'Store',
    ],

    'customer_type' => [
        'b2b' => 'Business (B2B)',
        'b2c' => 'Individual (B2C)',
    ],

    'payment_terms' => [
        'due_on_receipt' => 'Due on receipt',
        'cod' => 'Cash on delivery',
        'prepaid' => 'Prepaid',
        'net_15' => 'Net 15',
        'net_30' => 'Net 30',
        'net_45' => 'Net 45',
        'net_60' => 'Net 60',
    ],

    'expense_category' => [
        'rent' => 'Rent',
        'utilities' => 'Utilities',
        'salaries' => 'Salaries & wages',
        'marketing' => 'Marketing',
        'supplies' => 'Supplies',
        'insurance' => 'Insurance',
        'maintenance' => 'Maintenance',
        'other' => 'Other',
    ],

    'cash_session_status' => [
        'open' => 'Open',
        'closed' => 'Closed',
    ],

    'stock_movement_type' => [
        'purchase_receipt' => 'Purchase receipt',
        'sale' => 'Sale',
        'sale_return' => 'Sale return',
        'sale_cancelled' => 'Sale cancelled',
        'purchase_return' => 'Purchase return',
        'adjustment' => 'Adjustment',
        'transfer_out' => 'Transfer out',
        'transfer_in' => 'Transfer in',
        'opening_stock' => 'Opening stock',
    ],

    'stock_adjustment_reason' => [
        'damage' => 'Damage',
        'loss' => 'Loss',
        'correction' => 'Correction',
        'other' => 'Other',
    ],

    'supplier_transaction_type' => [
        'charge' => 'Charge (invoice received)',
        'payment' => 'Payment made',
        'return' => 'Return (credit)',
    ],

    'customer_transaction_type' => [
        'credit_sale' => 'Sale on account (owed)',
        'payment' => 'Payment received',
        'return' => 'Return (credit)',
        'sale_cancelled' => 'Sale cancelled (voided)',
    ],

    'purchase_order_status' => [
        'draft' => 'Draft',
        'pending_approval' => 'Pending approval',
        'ordered' => 'Ordered',
        'partially_received' => 'Partially received',
        'received' => 'Received',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],

    'stock_transfer_status' => [
        'draft' => 'Draft',
        'pending_approval' => 'Pending approval',
        'approved' => 'Approved',
        'in_transit' => 'In transit',
        'partially_received' => 'Partially received',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected',
    ],

    'sale_status' => [
        'draft' => 'Draft',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'payment_method' => [
        'cash' => 'Cash',
        'card' => 'Card',
        'credit' => 'On account (pay later)',
    ],

    'discount_type' => [
        'percentage' => 'Percentage',
        'fixed' => 'Fixed amount',
    ],

    'barcode_format' => [
        'ean13' => 'EAN-13',
        'ean8' => 'EAN-8',
        'upca' => 'UPC-A',
        'code128' => 'Code 128',
        'code39' => 'Code 39',
    ],

    'business_type' => [
        'retail' => 'Retail',
        'restaurant' => 'Restaurant',
        'pharmacy' => 'Pharmacy',
        'wholesale' => 'Wholesale',
        'other' => 'Other',
    ],

    'currency' => [
        'USD' => 'USD - US Dollar',
        'EUR' => 'EUR - Euro',
        'GBP' => 'GBP - British Pound',
        'AED' => 'AED - UAE Dirham',
        'SAR' => 'SAR - Saudi Riyal',
        'EGP' => 'EGP - Egyptian Pound',
        'JOD' => 'JOD - Jordanian Dinar',
        'LBP' => 'LBP - Lebanese Pound',
        'CAD' => 'CAD - Canadian Dollar',
        'AUD' => 'AUD - Australian Dollar',
    ],

    'navigation_group' => [
        'sales' => 'Sales',
        'purchasing' => 'Purchasing',
        'catalog' => 'Catalog',
        'inventory' => 'Inventory',
        'finance' => 'Finance',
        'reports' => 'Reports',
        'settings' => 'Settings',
        'system' => 'System',
    ],

];
