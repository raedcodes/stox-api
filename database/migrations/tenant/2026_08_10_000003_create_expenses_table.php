<?php

use App\Enums\ExpenseCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default(ExpenseCategory::Other->value);
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            // Nullable: some expenses are location-specific (a store's
            // rent), others are company-wide (a software subscription).
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_date', 'location_id'], 'expenses_date_location_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
