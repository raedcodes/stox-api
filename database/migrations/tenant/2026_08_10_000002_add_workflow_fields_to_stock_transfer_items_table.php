<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supports the real ship/receive workflow: quantity_shipped is set once
     * when the transfer is marked in transit (may be less than requested
     * quantity if FEFO couldn't fully cover it — insufficientStockProducts()
     * still guards that, but this stays defensive). shipped_unit_cost /
     * shipped_expiry_date capture what actually left the source — a
     * weighted-average cost and earliest expiry across whichever source
     * batches FEFO consumed — so the destination batch created at receive
     * time carries the right cost basis and expiry without needing to
     * track each individual source batch through the transit leg.
     */
    public function up(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->decimal('quantity_shipped', 12, 2)->nullable()->after('quantity');
            $table->decimal('quantity_received', 12, 2)->default(0)->after('quantity_shipped');
            $table->decimal('shipped_unit_cost', 12, 2)->nullable()->after('quantity_received');
            $table->date('shipped_expiry_date')->nullable()->after('shipped_unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn(['quantity_shipped', 'quantity_received', 'shipped_unit_cost', 'shipped_expiry_date']);
        });
    }
};
