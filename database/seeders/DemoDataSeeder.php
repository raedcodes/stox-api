<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SaleStatus;
use App\Enums\StockAdjustmentReason;
use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Enums\SupplierTransactionType;
use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\Staff;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo-only: populates a tenant with a realistic multi-location retail
 * catalog, stock across a spread of expiry dates, supplier/customer
 * ledgers, and 60 days of sales history — so every dashboard widget,
 * report, and ledger has something meaningful to show.
 *
 * Not run automatically for real tenants (unlike TenantDatabaseSeeder) —
 * this is opt-in, via:
 *   php artisan tenants:seed --class="DemoDataSeeder" --tenants=<tenant-id>
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedStaff();
            $locations = $this->seedLocations();
            [$categories, $brands] = [$this->seedCategories(), $this->seedBrands()];
            $products = $this->seedProducts($categories, $brands);
            $this->seedBatches($products, $locations);
            $this->seedProductVariants($products, $locations);
            $this->seedStockAdjustments();
            $this->seedStockTransfers($products, $locations);
            $customers = $this->seedCustomers();
            $suppliers = $this->seedSuppliers();
            $this->seedPurchaseOrders($suppliers, $products, $locations);
            $this->seedPurchaseReturns($suppliers, $products, $locations);
            $this->seedSales($customers, $products, $locations);
            $this->seedPartialPayments($customers, $suppliers);
            $this->seedLowStockProducts($products);
        });
    }

    /**
     * A predictable Manager/Cashier/Storekeeper login for demoing
     * role-based access. No Owner account here — every tenant already has
     * exactly one, made when the business itself was provisioned (see
     * StaffForm::assignableRoles()'s docblock) — and its password isn't
     * touched by this seeder.
     */
    protected function seedStaff(): void
    {
        Staff::firstOrCreate(
            ['email' => 'storekeeper@demo.test'],
            [
                'name' => 'Demo Storekeeper',
                'role' => UserRole::StoreKeeper->value,
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        Staff::firstOrCreate(
            ['email' => 'manager@demo.test'],
            [
                'name' => 'Demo Manager',
                'role' => UserRole::Manager->value,
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        Staff::firstOrCreate(
            ['email' => 'cashier@demo.test'],
            [
                'name' => 'Demo Cashier',
                'role' => UserRole::Cashier->value,
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * @return Collection<int, Location>
     */
    protected function seedLocations(): Collection
    {
        $main = Location::firstOrCreate(
            ['name' => 'Main Location'],
            ['type' => LocationType::Store, 'is_active' => true],
        );

        $warehouse = Location::firstOrCreate(
            ['name' => 'Main Warehouse'],
            ['type' => LocationType::Warehouse, 'is_active' => true],
        );

        $downtown = Location::firstOrCreate(
            ['name' => 'Downtown Storefront'],
            ['type' => LocationType::Store, 'is_active' => true],
        );

        return new Collection([$main, $warehouse, $downtown]);
    }

    /**
     * @return Collection<int, Category>
     */
    protected function seedCategories(): Collection
    {
        $names = ['Electronics', 'Groceries & FMCG', 'Pharmaceuticals', 'Household Supplies', 'Beverages', 'Personal Care'];

        return new Collection(collect($names)
            ->map(fn (string $name) => Category::firstOrCreate(['name' => $name], ['is_active' => true]))
            ->all());
    }

    /**
     * Built the same way as seedCategories() — a plain fixed-name loop, not
     * the factory's fake()->unique(), which exhausts its small pool of
     * options after the first tenant when tenants:seed runs for several
     * tenants in one process (Faker's uniqueness tracking is per-process,
     * not per model-creation batch).
     *
     * @return Collection<int, Brand>
     */
    protected function seedBrands(): Collection
    {
        $names = ['Nova', 'Meridian', 'Everstone', 'BluePeak', 'Solace', 'Cascade'];

        return new Collection(collect($names)
            ->map(fn (string $name) => Brand::firstOrCreate(['name' => $name], ['is_active' => true]))
            ->all());
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, Brand>  $brands
     * @return Collection<int, Product>
     */
    protected function seedProducts(Collection $categories, Collection $brands): Collection
    {
        $unit = UnitOfMeasure::query()->where('abbreviation', 'pc')->first()
            ?? UnitOfMeasure::query()->firstOrFail();

        $categoriesByName = $categories->keyBy('name');
        $products = collect();

        foreach (\Database\Factories\ProductFactory::catalog() as $categoryName => $entries) {
            $category = $categoriesByName->get($categoryName);
            $prefix = $this->skuPrefixFor($categoryName);

            foreach ($entries as $index => $entry) {
                $products->push(Product::firstOrCreate(
                    ['name' => $entry['name']],
                    [
                        'sku' => sprintf('%s-%03d', $prefix, $index + 1),
                        'description' => $entry['description'],
                        'barcode' => $this->generateBarcode($entry['name']),
                        'cost_price' => $entry['cost'],
                        'selling_price' => $entry['price'],
                        'wsp' => round($entry['price'] * 0.85, 2),
                        'category_id' => $category?->id,
                        'brand_id' => $brands->random()->id,
                        'unit_of_measure_id' => $unit->id,
                        'min_stock_level' => 20,
                        'reorder_level' => 50,
                        'tax_inclusive' => false,
                        'is_active' => true,
                    ],
                ));
            }
        }

        return new Collection($products->all());
    }

    /**
     * Categories that realistically expire — everything else gets
     * no-expiry, long-shelf-life batches instead.
     */
    protected function isPerishableCategory(?string $categoryName): bool
    {
        return in_array($categoryName, ['Groceries & FMCG', 'Pharmaceuticals', 'Beverages', 'Personal Care'], true);
    }

    /**
     * Short category codes for SKUs (e.g. "ELEC-001") — standard practice is
     * a category/brand prefix plus a sequence number, not the full product
     * name run together.
     */
    protected function skuPrefixFor(?string $categoryName): string
    {
        return match ($categoryName) {
            'Electronics' => 'ELEC',
            'Groceries & FMCG' => 'GRC',
            'Pharmaceuticals' => 'PHR',
            'Household Supplies' => 'HH',
            'Beverages' => 'BEV',
            'Personal Care' => 'PC',
            default => 'GEN',
        };
    }

    /**
     * A real, valid EAN-13 (correct check digit) so barcode scan-to-cart in
     * the demo POS actually has something scannable to demonstrate — not
     * just a random-looking number. Deterministic per name, so re-running
     * the seeder against an existing demo tenant doesn't reshuffle barcodes
     * on products firstOrCreate() already found.
     */
    protected function generateBarcode(string $seed): string
    {
        $digits = str_pad((string) (crc32($seed) % 1_000_000_000_000), 12, '0', STR_PAD_LEFT);

        $sum = 0;

        foreach (str_split($digits) as $position => $digit) {
            $sum += $position % 2 === 0 ? (int) $digit : (int) $digit * 3;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $digits.$checkDigit;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Location>  $locations
     */
    protected function seedBatches(Collection $products, Collection $locations): void
    {
        [$store, $warehouse, $downtown] = [$locations[0], $locations[1], $locations[2]];

        foreach ($products as $product) {
            $perishable = $this->isPerishableCategory($product->category?->name);

            if ($product->batches()->where('location_id', $warehouse->id)->exists()) {
                continue;
            }

            // Each batch gets its own small variance off the product's cost
            // price — real landed costs vary batch to batch (different
            // shipments, exchange rates, freight), not a single flat number
            // repeated everywhere.
            $unitCost = fn () => round((float) $product->cost_price * fake()->randomFloat(2, 0.92, 1.08), 2);

            if ($perishable) {
                ProductBatch::factory()->for($product)->for($warehouse, 'location')->expiringSoon()->create(['unit_cost' => $unitCost()]);
                ProductBatch::factory()->for($product)->for($warehouse, 'location')->expiringSoonish()->create(['unit_cost' => $unitCost()]);
                ProductBatch::factory()->for($product)->for($warehouse, 'location')->longDated()->create(['unit_cost' => $unitCost()]);
                ProductBatch::factory()->for($product)->for($warehouse, 'location')->expired()->create(['unit_cost' => $unitCost()]);
                ProductBatch::factory()->for($product)->for($store, 'location')->expiringSoonish()->create(['unit_cost' => $unitCost()]);
                ProductBatch::factory()->for($product)->for($downtown, 'location')->longDated()->create(['unit_cost' => $unitCost()]);
            } else {
                ProductBatch::factory()->for($product)->for($warehouse, 'location')->noExpiry()->create(['unit_cost' => $unitCost()]);
                ProductBatch::factory()->for($product)->for($store, 'location')->noExpiry()->create(['unit_cost' => $unitCost()]);
                ProductBatch::factory()->for($product)->for($downtown, 'location')->noExpiry()->create(['unit_cost' => $unitCost()]);
            }
        }
    }

    /**
     * Real Size/Color variants for a handful of products that plausibly
     * have them — every product already has its one auto-created default
     * variant (Product::booted()'s `created` hook), so this is on top of
     * that, not instead of it. Each new variant gets its own small batch
     * at the warehouse, so it has real stock to show, not just an empty
     * row on the product's Variants tab.
     *
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Location>  $locations
     */
    protected function seedProductVariants(Collection $products, Collection $locations): void
    {
        $warehouse = $locations[1];
        $variantProductNames = ['Wireless Bluetooth Earbuds', 'Wireless Mouse', 'Mechanical Keyboard'];

        foreach ($products->whereIn('name', $variantProductNames) as $product) {
            // Already has more than its one auto-created default — this
            // step already ran for it.
            if ($product->variants()->count() > 1) {
                continue;
            }

            foreach (['Black', 'White'] as $color) {
                $variant = $product->variants()->create([
                    'color' => $color,
                    'is_active' => true,
                ]);

                ProductBatch::factory()->for($product)->for($warehouse, 'location')->noExpiry()->create([
                    'product_variant_id' => $variant->id,
                    'unit_cost' => round((float) $product->cost_price * fake()->randomFloat(2, 0.92, 1.08), 2),
                ]);
            }
        }
    }

    /**
     * A handful of manual stock corrections against existing batches, with
     * a realistic mix of reasons — damage/loss (always a decrease) and
     * correction (either direction, e.g. a recount finding more or less
     * than expected).
     */
    protected function seedStockAdjustments(): void
    {
        if (StockAdjustment::query()->exists()) {
            return;
        }

        $batches = ProductBatch::query()->where('is_active', true)->inRandomOrder()->limit(5)->get();

        foreach ($batches as $batch) {
            $reason = fake()->randomElement([
                StockAdjustmentReason::Damage,
                StockAdjustmentReason::Loss,
                StockAdjustmentReason::Correction,
            ]);

            $isIncrease = $reason === StockAdjustmentReason::Correction && fake()->boolean();

            $quantityChange = $isIncrease
                ? random_int(1, 10)
                : -min(random_int(1, 10), max((int) floor((float) $batch->quantity), 1));

            $adjustment = StockAdjustment::create([
                'product_id' => $batch->product_id,
                'product_variant_id' => $batch->product_variant_id,
                'location_id' => $batch->location_id,
                'product_batch_id' => $batch->id,
                'quantity_change' => $quantityChange,
                'reason' => $reason,
                'notes' => 'Demo data — '.$reason->getLabel().' found during a routine check.',
                'adjustment_date' => now()->subDays(random_int(0, 30)),
            ]);

            $adjustment->apply();
        }
    }

    /**
     * Warehouse-to-store transfers — the same route the business actually
     * restocks its two storefronts from. One is left incomplete (Draft) so
     * the list also shows a pending transfer, not just finished ones.
     *
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Location>  $locations
     */
    protected function seedStockTransfers(Collection $products, Collection $locations): void
    {
        if (StockTransfer::query()->exists()) {
            return;
        }

        [$store, $warehouse, $downtown] = [$locations[0], $locations[1], $locations[2]];
        $routes = [[$warehouse, $store], [$warehouse, $downtown]];

        foreach ($routes as $index => [$from, $to]) {
            $transfer = StockTransfer::create([
                'from_location_id' => $from->id,
                'to_location_id' => $to->id,
                'status' => StockTransferStatus::Draft,
                'transfer_date' => now()->subDays(random_int(1, 20)),
                'notes' => 'Demo data.',
            ]);

            foreach ($products->random(3) as $product) {
                $available = $product->stockOnHand($from->id);

                if ($available < 1) {
                    continue;
                }

                $transfer->items()->create([
                    'product_id' => $product->id,
                    'quantity' => min(random_int(1, 10), (int) floor($available)),
                ]);
            }

            if ($transfer->items()->count() === 0) {
                $transfer->delete();

                continue;
            }

            // Leave the last route as an open Draft, the rest completed.
            if ($index < count($routes) - 1) {
                $transfer->complete();
            }
        }
    }

    /**
     * @return Collection<int, Customer>
     */
    protected function seedCustomers(): Collection
    {
        return Customer::query()->where('is_walk_in', false)->count() >= 10
            ? Customer::query()->where('is_walk_in', false)->limit(10)->get()
            : Customer::factory()->count(10)->create();
    }

    /**
     * @return Collection<int, Supplier>
     */
    protected function seedSuppliers(): Collection
    {
        return Supplier::query()->count() >= 5
            ? Supplier::query()->limit(5)->get()
            : Supplier::factory()->count(5)->create();
    }

    /**
     * Creates purchase orders across the last 60 days; most are received
     * (simulating a simplified receipt — a real batch created, item
     * quantity_received updated, and a Supplier ledger charge posted) so
     * Accounts Payable has real, varied balances. A couple stay "Ordered"
     * to also show open orders in the list.
     *
     * @param  Collection<int, Supplier>  $suppliers
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Location>  $locations
     */
    protected function seedPurchaseOrders(Collection $suppliers, Collection $products, Collection $locations): void
    {
        $warehouse = $locations[1];

        for ($i = 0; $i < 8; $i++) {
            $supplier = $suppliers->random();
            $orderDate = now()->subDays(random_int(1, 55));

            $po = PurchaseOrder::create([
                'supplier_id' => $supplier->id,
                'location_id' => $warehouse->id,
                'status' => PurchaseOrderStatus::Ordered,
                'order_date' => $orderDate,
                'expected_date' => $orderDate->copy()->addDays(7),
            ]);

            $lineProducts = $products->random(random_int(2, 4));
            $shouldReceive = $i < 6;
            $totalCharge = 0;

            foreach ($lineProducts as $product) {
                $quantity = random_int(50, 150);
                $unitCost = (float) $product->cost_price;

                $item = $po->items()->create([
                    'product_id' => $product->id,
                    'quantity_ordered' => $quantity,
                    'unit_cost' => $unitCost,
                ]);

                if (! $shouldReceive) {
                    continue;
                }

                $batch = ProductBatch::create([
                    'product_id' => $product->id,
                    'location_id' => $warehouse->id,
                    'batch_number' => 'PO-'.$po->id.'-'.$product->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                ]);

                $item->update(['quantity_received' => $quantity]);

                StockMovement::record(
                    batch: $batch,
                    type: StockMovementType::PurchaseReceipt,
                    quantityChange: $quantity,
                    reference: $po,
                );

                $totalCharge += $quantity * $unitCost;
            }

            if ($shouldReceive && $totalCharge > 0) {
                $supplier->transactions()->create([
                    'type' => SupplierTransactionType::Charge,
                    'amount' => $totalCharge,
                    'description' => "Receipt against {$po->po_number}",
                    'transaction_date' => $orderDate->copy()->addDays(random_int(1, 5)),
                    'reference_type' => $po->getMorphClass(),
                    'reference_id' => $po->id,
                ]);

                $po->recomputeStatus();
            }
        }
    }

    /**
     * A purchase return against a freshly received order (its own,
     * self-contained order — not one of the eight from seedPurchaseOrders(),
     * so this doesn't depend on which of those happened to be received)
     * — half the received quantity returned to the supplier for being
     * damaged on arrival, processed through PurchaseReturn::process() so
     * stock is FEFO-deducted and the supplier ledger carries a real
     * Return-type transaction, exactly as a real return would.
     *
     * @param  Collection<int, Supplier>  $suppliers
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Location>  $locations
     */
    protected function seedPurchaseReturns(Collection $suppliers, Collection $products, Collection $locations): void
    {
        if (PurchaseReturn::query()->exists()) {
            return;
        }

        $warehouse = $locations[1];
        $supplier = $suppliers->random();
        $product = $products->random();
        $orderDate = now()->subDays(30);
        $quantity = 40;
        $unitCost = (float) $product->cost_price;

        $po = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'location_id' => $warehouse->id,
            'status' => PurchaseOrderStatus::Ordered,
            'order_date' => $orderDate,
            'expected_date' => $orderDate->copy()->addDays(7),
        ]);

        $item = $po->items()->create([
            'product_id' => $product->id,
            'quantity_ordered' => $quantity,
            'unit_cost' => $unitCost,
        ]);

        $batch = ProductBatch::create([
            'product_id' => $product->id,
            'location_id' => $warehouse->id,
            'batch_number' => 'PO-'.$po->id.'-'.$product->id,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
        ]);

        $item->update(['quantity_received' => $quantity]);

        StockMovement::record(
            batch: $batch,
            type: StockMovementType::PurchaseReceipt,
            quantityChange: $quantity,
            reference: $po,
        );

        $supplier->transactions()->create([
            'type' => SupplierTransactionType::Charge,
            'amount' => $quantity * $unitCost,
            'description' => "Receipt against {$po->po_number}",
            'transaction_date' => $orderDate->copy()->addDays(2),
            'reference_type' => $po->getMorphClass(),
            'reference_id' => $po->id,
        ]);

        $po->recomputeStatus();

        $return = PurchaseReturn::create([
            'purchase_order_id' => $po->id,
            'return_date' => now()->subDays(10),
            'reason' => 'Damaged on arrival',
        ]);

        $return->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => (int) round($quantity / 2),
            'unit_cost' => $unitCost,
        ]);

        $return->process();
    }

    /**
     * 55 sales spread across the last 60 days, with a realistic payment
     * method mix (mostly cash/card, some on account), so revenue/COGS trend
     * charts and the customer ledger both have real history.
     *
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Location>  $locations
     */
    protected function seedSales(Collection $customers, Collection $products, Collection $locations): void
    {
        $sellingLocations = [$locations[0], $locations[2]];

        for ($i = 0; $i < 55; $i++) {
            $location = $sellingLocations[array_rand($sellingLocations)];
            $paymentMethod = collect([
                PaymentMethod::Cash, PaymentMethod::Cash,
                PaymentMethod::Card, PaymentMethod::Card,
                PaymentMethod::Credit,
            ])->random();

            // The first few are pinned to today (not left to the random day
            // offset below) so the dashboard's "Today's sales/revenue" KPIs
            // always have something to show, not just whichever historical
            // days happened to get a random hit.
            $saleDate = $i < 3 ? now() : now()->subDays(random_int(0, 59));

            $sale = Sale::create([
                'customer_id' => $customers->random()->id,
                'location_id' => $location->id,
                'payment_method' => $paymentMethod,
                'sale_date' => $saleDate->setTime(random_int(9, 20), random_int(0, 59)),
                'status' => SaleStatus::Draft,
            ]);

            foreach ($products->random(random_int(1, 4)) as $product) {
                $available = $product->stockOnHand($location->id);
                $quantity = min(random_int(1, 5), max((int) floor($available), 1));

                if ($available < 1) {
                    continue;
                }

                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->selling_price,
                ]);
            }

            if ($sale->items()->count() === 0) {
                $sale->delete();

                continue;
            }

            try {
                $sale->complete();
            } catch (\RuntimeException) {
                // Ran out of stock for this random combination — skip, the
                // rest of the demo data doesn't depend on hitting exactly 55.
                $sale->items()->delete();
                $sale->delete();
            }
        }
    }

    /**
     * A handful of partial payments against credit sales/purchases, so
     * outstanding balances look like a real, partially-collected ledger
     * rather than every credit transaction sitting fully unpaid.
     *
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, Supplier>  $suppliers
     */
    protected function seedPartialPayments(Collection $customers, Collection $suppliers): void
    {
        foreach ($customers as $customer) {
            $balance = $customer->balance();

            if ($balance <= 0) {
                continue;
            }

            if (! fake()->boolean(50)) {
                continue;
            }

            $customer->transactions()->create([
                'type' => \App\Enums\CustomerTransactionType::Payment,
                'amount' => round($balance * fake()->randomFloat(2, 0.3, 0.7), 2),
                'description' => 'Partial payment received',
                'transaction_date' => now()->subDays(random_int(0, 15)),
            ]);
        }

        foreach ($suppliers as $supplier) {
            $balance = $supplier->balance();

            if ($balance <= 0) {
                continue;
            }

            if (! fake()->boolean(50)) {
                continue;
            }

            $supplier->transactions()->create([
                'type' => SupplierTransactionType::Payment,
                'amount' => round($balance * fake()->randomFloat(2, 0.3, 0.7), 2),
                'description' => 'Partial payment made',
                'transaction_date' => now()->subDays(random_int(0, 15)),
            ]);
        }
    }

    /**
     * seedBatches() gives every product hundreds of units across three
     * locations, so nothing ever naturally dips below its reorder level —
     * the dashboard's "Low / out of stock" KPI and the Low Stock widget
     * would otherwise always read zero. Deliberately depletes two specific
     * products down to a known state (via real StockAdjustment::apply()
     * calls, not a raw update) so both have something to show. Runs last,
     * after sales/transfers/adjustments, so nothing earlier trips over
     * these two having little or no stock.
     *
     * @param  Collection<int, Product>  $products
     */
    protected function seedLowStockProducts(Collection $products): void
    {
        $outOfStock = $products->firstWhere('name', 'Cough Syrup 100ml');

        if ($outOfStock && $outOfStock->stockOnHand() > 0) {
            foreach ($outOfStock->batches()->where('is_active', true)->get() as $batch) {
                $this->depleteBatch($batch);
            }
        }

        $lowStock = $products->firstWhere('name', 'Allergy Relief Tablets (10ct)');
        $target = 10; // below every seeded product's reorder_level of 50

        if ($lowStock && $lowStock->stockOnHand() > $target) {
            $batches = $lowStock->batches()->where('is_active', true)->get();

            foreach ($batches as $index => $batch) {
                $keep = $index === $batches->count() - 1 ? min($target, (float) $batch->quantity) : 0;

                $this->depleteBatch($batch, (float) $batch->quantity - $keep);
            }
        }
    }

    /**
     * Reduce a batch's quantity via a real stock adjustment rather than a
     * raw update — logs a StockMovement and shows up in the batch/product's
     * history exactly like a real recount-driven correction would.
     */
    protected function depleteBatch(ProductBatch $batch, ?float $decrease = null): void
    {
        $decrease ??= (float) $batch->quantity;

        if ($decrease <= 0) {
            return;
        }

        $adjustment = StockAdjustment::create([
            'product_id' => $batch->product_id,
            'product_variant_id' => $batch->product_variant_id,
            'location_id' => $batch->location_id,
            'product_batch_id' => $batch->id,
            'quantity_change' => -$decrease,
            'reason' => StockAdjustmentReason::Correction,
            'notes' => 'Demo data — recount found less on hand than expected.',
            'adjustment_date' => now()->subDays(random_int(0, 5)),
        ]);

        $adjustment->apply();
    }
}
