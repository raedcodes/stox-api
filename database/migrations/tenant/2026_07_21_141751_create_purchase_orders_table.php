<?php

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('po_number')->unique();
            $table->string('status')->default(PurchaseOrderStatus::Draft);
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Every dashboard widget and report filters by status + a date
            // range together.
            $table->index(['status', 'order_date'], 'purchase_orders_status_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
