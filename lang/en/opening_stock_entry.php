<?php

return [

    'navigation_label' => 'Opening Stock Entry',
    'saved' => 'Opening stock recorded',

    'actions' => [
        'import_excel' => 'Import Excel',
        'download_import_template' => 'Download Template',
    ],

    'import' => [
        'file_helper' => 'Upload a CSV or Excel file. Download the template above for the expected columns.',
        'sku_not_found' => 'SKU ":sku" was not found. The product must already exist before importing its opening stock.',
        'location_not_found' => 'Location ":location" was not found or is inactive.',
        'location_ambiguous' => 'Location ":location" matches :count active locations — rename one to make it unique.',
        'success_title' => 'Import complete',
        'success_body' => ':count opening stock line(s) recorded.',
        'partial_title' => 'Import completed with errors — :imported imported, :failed failed',
        'all_failed_title' => 'Import failed — 0 rows imported',
        'row_error' => 'Row :row: :message',
        'more_errors' => 'and :count more',
        'failed_title' => 'Import failed',
    ],

];
