<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A sale's actual tender breakdown — one row per payment method used
     * (a plain single-method sale still gets exactly one row). Replaces
     * sales.payment_method as the source of truth; that column stays for
     * backward-compat display only. See Sale::paymentLines().
     */
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('payment_method');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
