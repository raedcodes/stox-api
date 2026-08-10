<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Null means unlimited — most walk-in/cash customers never touch
            // credit at all, so a limit is opt-in per customer, not a
            // default every record has to carry.
            $table->decimal('credit_limit', 12, 2)->nullable()->after('customer_type');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('credit_limit');
        });
    }
};
