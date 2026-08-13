<?php

return [

    'model_label' => 'فئة',
    'plural_model_label' => 'الفئات',

    'fields' => [
        'parent_id' => 'الفئة الأصلية',
        'parent_id_helper' => 'اتركه فارغاً لفئة رئيسية.',
    ],

    'columns' => [
        'category' => 'الفئة',
        'products' => 'المنتجات',
        'subcategories' => 'الفئات الفرعية',
    ],

    'filters' => [
        'top_level' => 'الفئات الرئيسية فقط',
        'parent_id' => 'الفئة الأصلية',
    ],

];
