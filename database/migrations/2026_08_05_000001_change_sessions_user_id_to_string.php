<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel's DatabaseSessionHandler writes Auth::id() into this column
     * separately from the (string, already-flexible) session payload blob.
     * Staff::getAuthIdentifier() returns a "staff:{id}" string so the
     * business guard's auth provider can tell a tenant Staff session apart
     * from a central User (Owner) session — this column has to be able to
     * hold that string too, not just a plain integer id.
     */
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }
};
