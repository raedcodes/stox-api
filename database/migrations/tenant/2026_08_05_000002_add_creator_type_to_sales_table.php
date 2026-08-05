<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `created_by` can now be either a tenant Staff id or a central User
     * (Owner) id — two independent auto-increment sequences, so a plain FK
     * can't disambiguate them. Made polymorphic (see Sale::cashier()),
     * matching how stock_movements.reference already works. Every existing
     * row predates staff accounts, so any non-null created_by is backfilled
     * to User::class.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('creator_type')->nullable()->after('created_by');
        });

        DB::table('sales')->whereNotNull('created_by')->update(['creator_type' => User::class]);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('creator_type');
        });
    }
};
