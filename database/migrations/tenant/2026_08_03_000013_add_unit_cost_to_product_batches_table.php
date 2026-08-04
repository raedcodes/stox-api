<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The actual cost basis for this specific batch — what was really paid
     * per unit (including any allocated landed costs), as opposed to
     * products.cost_price, which is just a live reference/standard cost.
     * Backfilled from the product's current cost_price as a one-time
     * approximation for batches that predate this column.
     */
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->nullable()->after('quantity');
        });

        DB::statement('
            UPDATE product_batches pb
            INNER JOIN products p ON p.id = pb.product_id
            SET pb.unit_cost = p.cost_price
            WHERE pb.unit_cost IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
