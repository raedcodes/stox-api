<?php

return [

    'navigation_label' => 'إدخال المخزون الافتتاحي',
    'saved' => 'تم تسجيل المخزون الافتتاحي',

    'actions' => [
        'import_excel' => 'استيراد Excel',
        'download_import_template' => 'تحميل القالب',
    ],

    'import' => [
        'file_helper' => 'قم بتحميل ملف CSV أو Excel. حمّل القالب أعلاه لمعرفة الأعمدة المطلوبة.',
        'sku_not_found' => 'رمز SKU ":sku" غير موجود. يجب أن يكون المنتج موجوداً مسبقاً قبل استيراد مخزونه الافتتاحي.',
        'location_not_found' => 'الموقع ":location" غير موجود أو غير نشط.',
        'location_ambiguous' => 'الموقع ":location" يطابق :count مواقع نشطة — أعد تسمية أحدها ليصبح فريداً.',
        'success_title' => 'اكتمل الاستيراد',
        'success_body' => 'تم تسجيل :count سطر/أسطر مخزون افتتاحي.',
        'partial_title' => 'اكتمل الاستيراد مع وجود أخطاء — تم استيراد :imported وفشل :failed',
        'all_failed_title' => 'فشل الاستيراد — لم يتم استيراد أي صف',
        'row_error' => 'الصف :row: :message',
        'more_errors' => 'و :count أخرى',
        'failed_title' => 'فشل الاستيراد',
    ],

];
