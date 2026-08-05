<?php

use App\Enums\SaleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('sale_number')->unique();
            $table->string('status')->default(SaleStatus::Draft);
            $table->string('payment_method')->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->dateTime('sale_date');
            $table->text('notes')->nullable();
            // Polymorphic (a tenant Staff member or the central Owner) — no
            // FK constraint possible across that split.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('creator_type')->nullable();
            $table->timestamps();

            // Every dashboard widget and report filters by status + a date
            // range together.
            $table->index(['status', 'sale_date'], 'sales_status_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
