<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How this specific refund is being paid out — chosen by the cashier
     * at refund time, independent of how the original sale was tendered
     * (a split sale doesn't need to be refunded in the same proportions).
     */
    public function up(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->string('refund_method')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropColumn('refund_method');
        });
    }
};
