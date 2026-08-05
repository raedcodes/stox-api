<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->dateTime('transaction_date');
            $table->nullableMorphs('reference');
            $table->timestamps();

            // balance() and the dashboard's outstanding-balance calc both
            // GROUP BY type per party.
            $table->index(['customer_id', 'type'], 'customer_transactions_customer_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_transactions');
    }
};
