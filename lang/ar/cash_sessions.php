<?php

return [

    'navigation_label' => 'درج النقدية',
    'model_label' => 'جلسة نقدية',
    'plural_model_label' => 'الجلسات النقدية',

    'fields' => [
        'opened' => 'تاريخ الفتح',
        'opened_by' => 'فتحها',
        'opening_float' => 'المبلغ الافتتاحي',
        'closed' => 'تاريخ الإغلاق',
        'expected' => 'المتوقع',
        'counted' => 'المعدود',
        'variance' => 'الفرق',
        'expected_cash_now' => 'النقد المتوقع حالياً',
        'counted_cash' => 'النقد المعدود',
    ],

    'actions' => [
        'open_till' => 'فتح الدرج',
        'close_till' => 'إغلاق الدرج',
    ],

    'notifications' => [
        'opened' => 'تم فتح الدرج',
        'cannot_open' => 'لا يمكن فتح الدرج',
        'closed' => 'تم إغلاق الدرج',
        'cannot_close' => 'لا يمكن إغلاق الدرج',
    ],

    'opening_float_helper' => 'النقد الموجود بالفعل في الدرج قبل تسجيل أي مبيعات.',
    'notes_helper' => 'وضّح أي فرق بين النقد المعدود والمتوقع، إن وجد.',

];
