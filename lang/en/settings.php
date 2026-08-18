<?php

return [

    'business_profile' => [
        'navigation_label' => 'Business Profile',
        'saved' => 'Business profile saved',
        'fields' => [
            'business_name' => 'Business name',
            'tax_number' => 'Tax number',
            'business_type' => 'Business type',
            'currency' => 'Currency',
            'timezone' => 'Timezone',
            'subdomain' => 'Subdomain',
        ],
    ],

    'tax' => [
        'navigation_label' => 'Tax Settings',
        'saved' => 'Tax settings saved',
        'fields' => [
            'rate' => 'Tax / VAT rate',
        ],
    ],

    'barcode' => [
        'navigation_label' => 'Barcode Settings',
        'saved' => 'Barcode settings saved',
        'prefix_helper' => 'Optional prefix included at the start of every generated barcode.',
        'prefix_numeric_helper' => 'This format only allows digits, up to :digits of them (the rest is reserved for the sequence number and check digit).',
        'next_number_helper' => 'The sequence number the next generated barcode will use.',
        'fields' => [
            'format' => 'Barcode format',
            'prefix' => 'Prefix',
            'next_number' => 'Next number',
        ],
    ],

];
