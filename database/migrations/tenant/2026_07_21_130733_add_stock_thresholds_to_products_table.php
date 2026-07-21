<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('min_stock_level', 12, 2)->nullable()->after('unit_of_measure_id');
            $table->decimal('reorder_level', 12, 2)->nullable()->after('min_stock_level');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['min_stock_level', 'reorder_level']);
        });
    }
};
