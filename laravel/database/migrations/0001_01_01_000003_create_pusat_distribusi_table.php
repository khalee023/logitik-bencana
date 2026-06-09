<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pusat_distribusi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kabupaten')->constrained('kabupaten');
            $table->string('nama', 150);
            $table->decimal('lat', 10, 8);
            $table->decimal('long_decimal', 11, 8);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pusat_distribusi');
    }
};
