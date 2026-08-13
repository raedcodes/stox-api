<?php

return [

    'model_label' => 'عميل',
    'plural_model_label' => 'العملاء',
    'no_limit' => 'بلا حد',
    'default_suffix' => ':name (افتراضي)',
    'reference_helper' => 'مثال: رقم إيصال أو مرجع.',

    'fields' => [
        'customer_type' => 'نوع العميل',
        'credit_limit' => 'الحد الائتماني',
        'credit_limit_helper' => 'اتركه فارغاً لعدم وجود حد. يمنع إتمام بيع بالدين إذا كان سيجعل العميل يتجاوز هذا الحد.',
        'current_balance' => 'الرصيد الحالي',
    ],

    'columns' => [
        'balance' => 'الرصيد',
    ],

    'actions' => [
        'record_payment' => 'تسجيل دفعة',
    ],

    'notifications' => [
        'payment_recorded' => 'تم تسجيل الدفعة',
        'exceeds_balance' => 'هذا المبلغ يتجاوز الرصيد المستحق — سيصبح الفرق رصيداً دائناً في الحساب.',
    ],

];
