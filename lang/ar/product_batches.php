<?php

return [

    'model_label' => 'دفعة منتج',
    'plural_model_label' => 'دفعات المنتجات',
    'section_title' => 'تفاصيل الدفعة',
    'no_expiry' => 'بلا انتهاء',
    'unit_cost_helper' => 'يُستخدم سعر تكلفة المنتج تلقائياً — يمكنك تجاوزه إذا اختلفت التكلفة الفعلية لهذه الدفعة (مثل تكاليف الشحن).',
    'expiry_date_helper' => 'اتركه فارغاً إذا كانت هذه الدفعة لا تنتهي.',
    'is_active_helper' => 'أوقف التفعيل لدفعة تم إعدامها أو استرجاعها أو استخدامها بالكامل.',

    'fields' => [
        'batch_number' => 'رقم الدفعة / اللوت',
        'unit_cost' => 'تكلفة الوحدة',
        'manufactured_date' => 'تاريخ الصنع',
        'expiry_date' => 'تاريخ الانتهاء',
    ],

    'columns' => [
        'batch_hash' => 'رقم الدفعة',
        'manufactured' => 'تاريخ الصنع',
    ],

    'filters' => [
        'expired' => 'منتهية',
        'expiring_soon' => 'تنتهي خلال 30 يوماً',
    ],

];
