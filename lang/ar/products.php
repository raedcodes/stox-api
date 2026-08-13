<?php

return [

    'model_label' => 'منتج',
    'plural_model_label' => 'المنتجات',
    'photos_section_title' => 'الصور',
    'details_section_title' => 'تفاصيل المنتج',
    'pricing_section_title' => 'التسعير',
    'sku_helper_create' => 'اتركه فارغاً للإنشاء التلقائي من الفئة، مثل ELEC-001.',
    'min_stock_level_helper' => 'الحد الأدنى الآمن — أقل من هذا تخاطر بنفاد المخزون.',
    'reorder_level_helper' => 'عندما ينخفض المخزون إلى هذا المستوى، يحين وقت إعادة الطلب. يجب أن يكون عند الحد الأدنى أو أعلى منه.',
    'wsp_helper' => 'سعر مرجعي لعملاء الجملة/التجاري. لا يُطبَّق تلقائياً عند البيع.',
    'cbm_helper' => 'حجم المتر المكعب لكل وحدة، لتخطيط الشحن/الحاويات.',
    'images_helper' => 'تُستخدم الصورة الأولى كصورة رئيسية للمنتج.',
    'variants_modal_heading' => 'المتغيرات — :name',
    'single' => 'منفرد',
    'variants_count' => ':count متغيرات',

    'fields' => [
        'unit' => 'الوحدة',
        'min_stock_level' => 'الحد الأدنى للمخزون',
        'reorder_level' => 'مستوى إعادة الطلب',
        'cost_price' => 'سعر التكلفة',
        'selling_price' => 'سعر البيع',
        'wsp' => 'سعر بيع الجملة',
        'cbm' => 'الحجم (م³)',
        'tax_inclusive' => 'سعر البيع يشمل الضريبة',
        'stock_on_hand' => 'المتوفر في المخزون',
        'total_stock' => 'إجمالي المخزون',
        'variants' => 'المتغيرات',
        'size' => 'المقاس',
        'color' => 'اللون',
        'is_default' => 'افتراضي',
        'tax_incl' => 'شامل الضريبة',
        'min_stock' => 'الحد الأدنى',
        'reorder_at' => 'إعادة الطلب عند',
        'cbm_short' => 'الحجم (م³)',
        'wsp_short' => 'سعر الجملة',
        'price_includes_tax' => 'السعر يشمل الضريبة',
    ],

    'actions' => [
        'view_variants' => 'عرض المتغيرات',
        'close' => 'إغلاق',
    ],

    'variant_helper_create' => 'اتركه فارغاً للإنشاء التلقائي من رمز المنتج والمقاس/اللون.',
    'notifications' => [
        'variant_deleted' => 'تم حذف المتغيّر',
        'cannot_delete_variant' => 'لا يمكن حذف المتغيّر',
        'variant_needs_one' => 'يجب أن يحتوي المنتج على متغيّر واحد على الأقل.',
        'variant_has_batches' => 'لا يزال هذا المتغيّر يحتوي على دفعات مخزون — قم بتعطيله بدلاً من حذفه.',
        'variant_has_history' => 'هذا المتغيّر له سجل معاملات (مبيعات أو مشتريات أو حركات مخزون) ولا يمكن حذفه. قم بتعطيله بدلاً من ذلك.',
    ],

];
