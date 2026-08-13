<?php

return [

    'model_label' => 'Category',
    'plural_model_label' => 'Categories',

    'fields' => [
        'parent_id' => 'Parent category',
        'parent_id_helper' => 'Leave empty for a top-level category.',
    ],

    'columns' => [
        'category' => 'Category',
        'products' => 'Products',
        'subcategories' => 'Subcategories',
    ],

    'filters' => [
        'top_level' => 'Top-level only',
        'parent_id' => 'Parent category',
    ],

];
