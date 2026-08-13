<?php

return [

    'model_label' => 'تحويل مخزون',
    'plural_model_label' => 'تحويلات المخزون',
    'section_title' => 'تفاصيل التحويل',
    'items_section_title' => 'العناصر',
    'mark_in_transit_description' => 'سيتم خصم المخزون من الموقع المصدر الآن. لن يستلمه الموقع الوجهة إلا بعد تأكيد الوصول.',

    'fields' => [
        'from_location' => 'من موقع',
        'to_location' => 'إلى موقع',
        'items_to_receive' => 'العناصر المطلوب استلامها',
        'remaining' => 'المتبقي',
        'receive_now' => 'استلام الآن',
    ],

    'columns' => [
        'transfer_number' => 'رقم التحويل',
        'from' => 'من',
        'to' => 'إلى',
    ],

    'actions' => [
        'submit_for_approval' => 'إرسال للموافقة',
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'mark_in_transit' => 'تعليم كقيد النقل',
        'cancel_transfer' => 'إلغاء التحويل',
        'receive' => 'استلام',
    ],

    'notifications' => [
        'submitted' => 'تم الإرسال للموافقة',
        'cannot_submit' => 'لا يمكن الإرسال',
        'approved' => 'تمت الموافقة على التحويل',
        'cannot_approve' => 'لا يمكن الموافقة',
        'rejected' => 'تم رفض التحويل',
        'cannot_reject' => 'لا يمكن الرفض',
        'marked_in_transit' => 'تم تعليم التحويل كقيد النقل',
        'cannot_mark_in_transit' => 'لا يمكن التعليم كقيد النقل',
        'cancelled' => 'تم إلغاء التحويل',
        'cannot_cancel' => 'لا يمكن الإلغاء',
        'nothing_to_receive' => 'لا يوجد ما يمكن استلامه',
        'received' => 'تم استلام التحويل',
        'cannot_receive' => 'لا يمكن استلام التحويل',
    ],

];
