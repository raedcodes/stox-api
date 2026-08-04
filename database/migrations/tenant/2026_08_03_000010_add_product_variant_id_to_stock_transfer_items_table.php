<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->restrictOnDelete();
        });

        DB::statement('
            UPDATE stock_transfer_items sti
            INNER JOIN product_variants pv ON pv.product_id = sti.product_id AND pv.is_default = 1
            SET sti.product_variant_id = pv.id
        ');

        DB::statement('ALTER TABLE stock_transfer_items MODIFY product_variant_id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
        });
    }
};
