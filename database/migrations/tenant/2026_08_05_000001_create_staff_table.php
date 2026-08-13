<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Staff (Manager/Cashier/StoreKeeper) live in this tenant's own database,
     * unlike the Owner/SuperAdmin who live centrally in `users` — see
     * App\Models\Staff for why. Email is unique only within this table, so
     * two different tenants can each have a staff member with the same
     * email address.
     */
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role');
            $table->boolean('is_active')->default(true);
            // Nullable: falls back to config('app.locale') when unset. Only
            // 'en'/'ar' are meaningful today (see SetLocale middleware).
            $table->string('locale', 5)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
