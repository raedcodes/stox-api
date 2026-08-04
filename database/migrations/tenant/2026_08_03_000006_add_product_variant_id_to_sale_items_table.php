<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->restrictOnDelete();
        });

        DB::statement('
            UPDATE sale_items si
            INNER JOIN product_variants pv ON pv.product_id = si.product_id AND pv.is_default = 1
            SET si.product_variant_id = pv.id
        ');

        DB::statement('ALTER TABLE sale_items MODIFY product_variant_id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
        });
    }
};
