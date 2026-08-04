<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * A realistic catalog grouped by category, shared with DemoDataSeeder so
     * every seeded product has a name/price/description that actually makes
     * sense for its category, rather than generic faker text.
     *
     * @return array<string, array<int, array{name: string, description: string, cost: float, price: float}>>
     */
    public static function catalog(): array
    {
        return [
            'Electronics' => [
                ['name' => 'Wireless Bluetooth Earbuds', 'description' => 'True wireless earbuds with charging case, up to 24hr battery life.', 'cost' => 18, 'price' => 39.99],
                ['name' => '20000mAh Power Bank', 'description' => 'Fast-charging portable power bank with dual USB-A and USB-C output.', 'cost' => 12, 'price' => 29.99],
                ['name' => 'USB-C Fast Charger 65W', 'description' => 'GaN fast charger, compatible with phones and laptops.', 'cost' => 9, 'price' => 24.99],
                ['name' => 'HDMI Cable 2m', 'description' => 'High-speed HDMI 2.1 cable, supports 4K@120Hz.', 'cost' => 3, 'price' => 9.99],
                ['name' => 'Wireless Mouse', 'description' => 'Ergonomic wireless mouse with silent click buttons.', 'cost' => 6, 'price' => 15.99],
                ['name' => 'Mechanical Keyboard', 'description' => 'Compact mechanical keyboard with hot-swappable switches.', 'cost' => 28, 'price' => 59.99],
                ['name' => 'Smart LED Light Bulb', 'description' => 'WiFi-enabled color-changing smart bulb, app controlled.', 'cost' => 5, 'price' => 12.99],
                ['name' => 'Phone Screen Protector', 'description' => 'Tempered glass screen protector, 9H hardness.', 'cost' => 1, 'price' => 6.99],
            ],
            'Groceries & FMCG' => [
                ['name' => 'Basmati Rice 5kg', 'description' => 'Premium aged basmati rice, extra long grain.', 'cost' => 6, 'price' => 11.99],
                ['name' => 'Extra Virgin Olive Oil 1L', 'description' => 'Cold-pressed extra virgin olive oil.', 'cost' => 5, 'price' => 10.99],
                ['name' => 'Whole Wheat Pasta 500g', 'description' => 'Durum wheat pasta, high in fiber.', 'cost' => 1, 'price' => 2.99],
                ['name' => 'Canned Chickpeas 400g', 'description' => 'Ready-to-eat chickpeas in brine.', 'cost' => 0.5, 'price' => 1.49],
                ['name' => 'Instant Coffee 200g', 'description' => 'Rich roast instant coffee granules.', 'cost' => 4, 'price' => 8.99],
                ['name' => 'Green Tea Bags (100ct)', 'description' => 'Pure green tea, antioxidant-rich.', 'cost' => 3, 'price' => 6.99],
                ['name' => 'Sparkling Water 1.5L', 'description' => 'Naturally carbonated spring water.', 'cost' => 0.6, 'price' => 1.99],
                ['name' => 'Dark Chocolate Bar 100g', 'description' => '70% cocoa dark chocolate.', 'cost' => 1.2, 'price' => 3.49],
            ],
            'Pharmaceuticals' => [
                ['name' => 'Paracetamol 500mg (20 tabs)', 'description' => 'Pain relief and fever reducer.', 'cost' => 1, 'price' => 3.49],
                ['name' => 'Ibuprofen 200mg (24 tabs)', 'description' => 'Anti-inflammatory pain relief tablets.', 'cost' => 1.5, 'price' => 4.99],
                ['name' => 'Vitamin C 1000mg (30 tabs)', 'description' => 'Immune support vitamin C tablets.', 'cost' => 3, 'price' => 8.99],
                ['name' => 'Cough Syrup 100ml', 'description' => 'Relieves dry and chesty cough.', 'cost' => 2, 'price' => 6.49],
                ['name' => 'Antiseptic Wound Spray 50ml', 'description' => 'Fast-acting antiseptic for minor cuts.', 'cost' => 2.5, 'price' => 7.99],
                ['name' => 'Hand Sanitizer Gel 250ml', 'description' => '70% alcohol hand sanitizer gel.', 'cost' => 1.2, 'price' => 3.99],
                ['name' => 'Allergy Relief Tablets (10ct)', 'description' => 'Non-drowsy antihistamine tablets.', 'cost' => 2, 'price' => 5.99],
                ['name' => 'Multivitamin Gummies (60ct)', 'description' => 'Daily multivitamin gummies for adults.', 'cost' => 4, 'price' => 12.99],
            ],
            'Household Supplies' => [
                ['name' => 'Laundry Detergent 3L', 'description' => 'Concentrated liquid laundry detergent.', 'cost' => 5, 'price' => 11.99],
                ['name' => 'Multi-Surface Cleaner 750ml', 'description' => 'All-purpose antibacterial surface cleaner.', 'cost' => 1.5, 'price' => 3.99],
                ['name' => 'Paper Towels (6 rolls)', 'description' => 'Absorbent 2-ply paper towels.', 'cost' => 3, 'price' => 6.99],
                ['name' => 'Trash Bags (30ct)', 'description' => 'Heavy-duty drawstring trash bags.', 'cost' => 2, 'price' => 5.49],
                ['name' => 'Dish Soap 500ml', 'description' => 'Grease-cutting dish washing liquid.', 'cost' => 1, 'price' => 2.99],
                ['name' => 'Air Freshener Spray', 'description' => 'Long-lasting room air freshener.', 'cost' => 1.5, 'price' => 3.99],
            ],
            'Beverages' => [
                ['name' => 'Orange Juice 1L', 'description' => '100% pure squeezed orange juice.', 'cost' => 1.5, 'price' => 3.49],
                ['name' => 'Cola 330ml (6-pack)', 'description' => 'Classic cola soft drink, 6-can pack.', 'cost' => 2.5, 'price' => 5.99],
                ['name' => 'Energy Drink 250ml', 'description' => 'Caffeinated energy drink.', 'cost' => 1, 'price' => 2.99],
                ['name' => 'Bottled Spring Water 500ml (12-pack)', 'description' => 'Natural spring water, 12-bottle pack.', 'cost' => 2, 'price' => 4.99],
            ],
            'Personal Care' => [
                ['name' => 'Shampoo 400ml', 'description' => 'Daily moisturizing shampoo.', 'cost' => 2.5, 'price' => 6.99],
                ['name' => 'Toothpaste 100ml', 'description' => 'Fluoride toothpaste for cavity protection.', 'cost' => 1, 'price' => 2.99],
                ['name' => 'Body Lotion 400ml', 'description' => 'Fragrance-free moisturizing body lotion.', 'cost' => 2, 'price' => 5.99],
                ['name' => 'Deodorant Spray 150ml', 'description' => '48-hour protection deodorant.', 'cost' => 1.5, 'price' => 3.99],
            ],
        ];
    }

    public function definition(): array
    {
        $entry = fake()->randomElement(collect(self::catalog())->flatten(1)->all());

        return [
            'name' => $entry['name'],
            'description' => $entry['description'],
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'barcode' => fake()->unique()->ean13(),
            'cost_price' => $entry['cost'],
            'selling_price' => $entry['price'],
            'wsp' => round($entry['price'] * 0.85, 2),
            'min_stock_level' => 20,
            'reorder_level' => 50,
            'tax_inclusive' => false,
            'is_active' => true,
        ];
    }
}
