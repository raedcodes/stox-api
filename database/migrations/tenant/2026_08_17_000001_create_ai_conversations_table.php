<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per user of the "Ask Stox" assistant. The actor is
     * polymorphic (user_id/user_type) rather than a plain user_id, since
     * the Owner (App\Models\User, central) and Manager (App\Models\Staff,
     * tenant-local) have independent id sequences that would otherwise
     * collide — same reasoning as Sale::created_by/creator_type.
     */
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type');
            $table->json('messages');
            $table->json('history');
            $table->timestamps();

            $table->unique(['user_id', 'user_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
