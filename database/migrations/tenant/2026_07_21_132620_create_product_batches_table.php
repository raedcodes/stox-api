<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('batch_number')->nullable();
            $table->decimal('quantity', 12, 2);
            // Actual cost basis for this specific batch — what was really
            // paid per unit (including any allocated landed costs) — as
            // opposed to products.cost_price, which is just a live
            // reference/standard cost.
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->date('manufactured_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Matches exactly the WHERE + ORDER BY that
            // ProductBatch::deductFefo() runs on every sale/transfer/return —
            // the single most frequently executed query in the app.
            $table->index(['product_variant_id', 'location_id', 'is_active', 'expiry_date'], 'product_batches_fefo_index');
            $table->index(['is_active', 'expiry_date'], 'product_batches_expiry_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
