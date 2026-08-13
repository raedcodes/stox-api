<?php

return [

    'model_label' => 'أمر شراء',
    'plural_model_label' => 'أوامر الشراء',
    'section_title' => 'تفاصيل أمر الشراء',
    'items_section_title' => 'العناصر',
    'landed_costs_section_title' => 'التكاليف الإضافية',
    'totals_section_title' => 'الإجماليات',
    'landed_costs_helper' => 'الشحن أو الجمارك أو المناولة أو أي تكلفة أخرى تُضاف على قيمة الطلب.',
    'approve_description' => 'سيتم إرسال هذا الطلب إلى المورّد.',

    'fields' => [
        'deliver_to' => 'التسليم إلى',
        'expected_date' => 'تاريخ التسليم المتوقع',
        'ordered' => 'المطلوب',
        'received' => 'المستلم',
        'landed_costs' => 'التكاليف الإضافية',
        'items_to_receive' => 'العناصر المطلوب استلامها',
        'remaining' => 'المتبقي',
        'receive_now' => 'استلام الآن',
        'batch_number' => 'رقم الدفعة',
    ],

    'actions' => [
        'submit_for_approval' => 'إرسال للموافقة',
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'cancel_order' => 'إلغاء الطلب',
        'print_po' => 'طباعة أمر الشراء',
        'receive_stock' => 'استلام المخزون',
        'add_charge' => 'إضافة تكلفة',
    ],

    'notifications' => [
        'submitted' => 'تم الإرسال للموافقة',
        'cannot_submit' => 'لا يمكن الإرسال',
        'approved' => 'تمت الموافقة على الطلب وإرساله',
        'cannot_approve' => 'لا يمكن الموافقة',
        'rejected' => 'تم رفض الطلب',
        'cannot_reject' => 'لا يمكن الرفض',
        'cancelled' => 'تم إلغاء الطلب',
        'cannot_cancel' => 'لا يمكن الإلغاء',
        'nothing_to_receive' => 'لا يوجد ما يمكن استلامه',
        'received' => 'تم استلام المخزون',
    ],

];
