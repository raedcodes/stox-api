<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every product needs exactly one "default" variant so stock tracking
     * (which now keys off product_variant_id) has somewhere to attach for
     * products that don't have real size/color variations. This backfills
     * one per existing product, reusing its own SKU/barcode.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('product_id');
        });

        $now = now();

        DB::table('products')
            ->select('id', 'sku', 'barcode')
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($now) {
                DB::table('product_variants')->insert(
                    $products->map(fn ($product) => [
                        'product_id' => $product->id,
                        'is_default' => true,
                        'sku' => $product->sku,
                        'barcode' => $product->barcode,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        DB::table('product_variants')->where('is_default', true)->delete();

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
