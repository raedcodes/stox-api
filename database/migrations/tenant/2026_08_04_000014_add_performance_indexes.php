<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes matching the actual hot-path query shapes in this app, not
     * generic guesses:
     *  - product_batches: exactly the WHERE + ORDER BY that
     *    ProductBatch::deductFefo() runs on every sale/transfer/return —
     *    the single most frequently executed query in the app.
     *  - sales / purchase_orders: every dashboard widget and report filters
     *    by status + a date range together.
     *  - *_transactions: balance() and the dashboard's outstanding-balance
     *    calc both GROUP BY type per party.
     */
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->index(['product_variant_id', 'location_id', 'is_active', 'expiry_date'], 'product_batches_fefo_index');
            $table->index(['is_active', 'expiry_date'], 'product_batches_expiry_index');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['status', 'sale_date'], 'sales_status_date_index');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index(['status', 'order_date'], 'purchase_orders_status_date_index');
        });

        Schema::table('customer_transactions', function (Blueprint $table) {
            $table->index(['customer_id', 'type'], 'customer_transactions_customer_type_index');
        });

        Schema::table('supplier_transactions', function (Blueprint $table) {
            $table->index(['supplier_id', 'type'], 'supplier_transactions_supplier_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropIndex('product_batches_fefo_index');
            $table->dropIndex('product_batches_expiry_index');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_status_date_index');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('purchase_orders_status_date_index');
        });

        Schema::table('customer_transactions', function (Blueprint $table) {
            $table->dropIndex('customer_transactions_customer_type_index');
        });

        Schema::table('supplier_transactions', function (Blueprint $table) {
            $table->dropIndex('supplier_transactions_supplier_type_index');
        });
    }
};
