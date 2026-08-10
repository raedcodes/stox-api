<?php

use App\Enums\CashSessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('status')->default(CashSessionStatus::Open->value);
            $table->decimal('opening_float', 12, 2);
            $table->timestamp('opened_at');
            $table->unsignedBigInteger('opened_by')->nullable();
            $table->string('opener_type')->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('counted_cash', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->string('closer_type')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['location_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
