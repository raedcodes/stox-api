<?php

return [

    'user_role' => [
        'super-admin' => 'مدير النظام',
        'owner' => 'المالك',
        'manager' => 'مدير',
        'cashier' => 'كاشير',
        'storekeeper' => 'أمين مخزن',
    ],

    'location_type' => [
        'warehouse' => 'مستودع',
        'store' => 'متجر',
    ],

    'customer_type' => [
        'b2b' => 'تجاري (B2B)',
        'b2c' => 'فرد (B2C)',
    ],

    'payment_terms' => [
        'due_on_receipt' => 'الدفع عند الاستلام',
        'cod' => 'الدفع عند التسليم',
        'prepaid' => 'مدفوع مسبقاً',
        'net_15' => 'صافي 15 يوماً',
        'net_30' => 'صافي 30 يوماً',
        'net_45' => 'صافي 45 يوماً',
        'net_60' => 'صافي 60 يوماً',
    ],

    'expense_category' => [
        'rent' => 'الإيجار',
        'utilities' => 'المرافق',
        'salaries' => 'الرواتب والأجور',
        'marketing' => 'التسويق',
        'supplies' => 'المستلزمات',
        'insurance' => 'التأمين',
        'maintenance' => 'الصيانة',
        'other' => 'أخرى',
    ],

    'cash_session_status' => [
        'open' => 'مفتوحة',
        'closed' => 'مغلقة',
    ],

    'stock_movement_type' => [
        'purchase_receipt' => 'استلام شراء',
        'sale' => 'بيع',
        'sale_return' => 'مرتجع بيع',
        'sale_cancelled' => 'بيع ملغى',
        'purchase_return' => 'مرتجع شراء',
        'adjustment' => 'تسوية',
        'transfer_out' => 'تحويل خارج',
        'transfer_in' => 'تحويل داخل',
        'opening_stock' => 'مخزون افتتاحي',
    ],

    'stock_adjustment_reason' => [
        'damage' => 'تلف',
        'loss' => 'فقدان',
        'correction' => 'تصحيح',
        'other' => 'أخرى',
    ],

    'supplier_transaction_type' => [
        'charge' => 'مستحق (فاتورة مستلمة)',
        'payment' => 'دفعة مسددة',
        'return' => 'مرتجع (دائن)',
    ],

    'customer_transaction_type' => [
        'credit_sale' => 'بيع بالدين (مستحق)',
        'payment' => 'دفعة مستلمة',
        'return' => 'مرتجع (دائن)',
        'sale_cancelled' => 'بيع ملغى',
    ],

    'purchase_order_status' => [
        'draft' => 'مسودة',
        'pending_approval' => 'بانتظار الموافقة',
        'ordered' => 'تم الطلب',
        'partially_received' => 'مستلم جزئياً',
        'received' => 'مستلم',
        'rejected' => 'مرفوض',
        'cancelled' => 'ملغى',
    ],

    'stock_transfer_status' => [
        'draft' => 'مسودة',
        'pending_approval' => 'بانتظار الموافقة',
        'approved' => 'معتمد',
        'in_transit' => 'قيد النقل',
        'partially_received' => 'مستلم جزئياً',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغى',
        'rejected' => 'مرفوض',
    ],

    'sale_status' => [
        'draft' => 'مسودة',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغى',
    ],

    'payment_method' => [
        'cash' => 'نقداً',
        'card' => 'بطاقة',
        'credit' => 'على الحساب (دفع لاحق)',
    ],

    'discount_type' => [
        'percentage' => 'نسبة مئوية',
        'fixed' => 'مبلغ ثابت',
    ],

    'barcode_format' => [
        'ean13' => 'EAN-13',
        'ean8' => 'EAN-8',
        'upca' => 'UPC-A',
        'code128' => 'Code 128',
        'code39' => 'Code 39',
    ],

    'business_type' => [
        'retail' => 'تجزئة',
        'restaurant' => 'مطعم',
        'pharmacy' => 'صيدلية',
        'wholesale' => 'جملة',
        'other' => 'أخرى',
    ],

    'currency' => [
        'USD' => 'USD - دولار أمريكي',
        'EUR' => 'EUR - يورو',
        'GBP' => 'GBP - جنيه إسترليني',
        'AED' => 'AED - درهم إماراتي',
        'SAR' => 'SAR - ريال سعودي',
        'EGP' => 'EGP - جنيه مصري',
        'JOD' => 'JOD - دينار أردني',
        'LBP' => 'LBP - ليرة لبنانية',
        'CAD' => 'CAD - دولار كندي',
        'AUD' => 'AUD - دولار أسترالي',
    ],

    'navigation_group' => [
        'sales' => 'المبيعات',
        'purchasing' => 'المشتريات',
        'catalog' => 'الكتالوج',
        'inventory' => 'المخزون',
        'finance' => 'المالية',
        'reports' => 'التقارير',
        'settings' => 'الإعدادات',
        'system' => 'النظام',
    ],

];
