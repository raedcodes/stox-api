<?php

use App\Enums\BarcodeFormat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barcode_settings', function (Blueprint $table) {
            $table->id();
            $table->string('format')->default(BarcodeFormat::CODE128);
            $table->string('prefix')->nullable();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_settings');
    }
};
