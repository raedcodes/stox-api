<?php

return [

    'model_label' => 'عملية بيع',
    'plural_model_label' => 'المبيعات',
    'section_title' => 'تفاصيل البيع',
    'totals_section_title' => 'الإجماليات',
    'items_section_title' => 'العناصر',
    'status_helper' => 'استخدم إجراء "إتمام البيع" لإنهاء عملية البيع — يؤدي ذلك إلى خصم المخزون وتحديث حساب العميل.',
    'tax_rate_helper' => 'هذه هي نسبة الضريبة المحددة على مستوى المنشأة في إعدادات الضرائب — تُطبّق تلقائياً على كل عملية بيع ولا يمكن تغييرها لكل فاتورة.',
    'cancel_sale_description' => 'يؤدي هذا إلى إعادة كل شيء في الفاتورة للمخزون وإلغاء أي مبلغ مستحق على حساب العميل. لا يمكن التراجع عن هذا الإجراء.',

    'fields' => [
        'tax_rate' => 'نسبة الضريبة',
        'invoice_discount_type' => 'نوع خصم الفاتورة',
        'invoice_discount_value' => 'قيمة خصم الفاتورة',
        'invoice_discount' => 'خصم الفاتورة',
    ],

    'actions' => [
        'complete_sale' => 'إتمام البيع',
        'print_invoice' => 'طباعة الفاتورة',
        'cancel_sale' => 'إلغاء البيع',
    ],

    'notifications' => [
        'completed' => 'تم إتمام البيع',
        'cannot_complete' => 'لا يمكن إتمام البيع',
        'cancelled' => 'تم إلغاء البيع',
        'cannot_cancel' => 'لا يمكن إلغاء البيع',
    ],

];
