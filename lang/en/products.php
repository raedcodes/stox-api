<?php

return [

    'model_label' => 'Product',
    'plural_model_label' => 'Products',
    'photos_section_title' => 'Photos',
    'details_section_title' => 'Product details',
    'pricing_section_title' => 'Pricing',
    'sku_helper_create' => 'Leave blank to auto-generate from the category, e.g. ELEC-001.',
    'min_stock_level_helper' => "The safety floor — below this, you risk running out.",
    'reorder_level_helper' => 'When stock drops to this level, it\'s time to reorder. Should be at or above the minimum.',
    'wsp_helper' => 'Reference price for wholesale/B2B customers. Not applied automatically at sale.',
    'cbm_helper' => 'Cubic meter volume per unit, for shipping/container planning.',
    'images_helper' => "The first image is used as the product's main photo.",
    'variants_modal_heading' => 'Variants — :name',
    'single' => 'Single',
    'variants_count' => ':count Variants',

    'fields' => [
        'unit' => 'Unit',
        'min_stock_level' => 'Minimum stock level',
        'reorder_level' => 'Reorder level',
        'cost_price' => 'Cost price',
        'selling_price' => 'Selling price',
        'wsp' => 'Wholesale selling price',
        'cbm' => 'CBM (volume, m³)',
        'tax_inclusive' => 'Selling price already includes tax',
        'stock_on_hand' => 'Stock on hand',
        'total_stock' => 'Total stock',
        'variants' => 'Variants',
        'size' => 'Size',
        'color' => 'Color',
        'is_default' => 'Default',
        'tax_incl' => 'Tax incl.',
        'min_stock' => 'Min stock',
        'reorder_at' => 'Reorder at',
        'cbm_short' => 'CBM (m³)',
        'wsp_short' => 'WSP',
        'price_includes_tax' => 'Price includes tax',
    ],

    'actions' => [
        'view_variants' => 'View Variants',
        'close' => 'Close',
    ],

    'variant_helper_create' => 'Leave blank to auto-generate from the product SKU and size/color.',
    'notifications' => [
        'variant_deleted' => 'Variant deleted',
        'cannot_delete_variant' => 'Cannot delete variant',
        'variant_needs_one' => 'A product must have at least one variant.',
        'variant_has_batches' => 'This variant still has stock batches — deactivate it instead.',
        'variant_has_history' => "This variant has transaction history (sales, purchases, or stock movements) and can't be deleted. Deactivate it instead.",
    ],

];
