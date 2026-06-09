<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kabupaten')->constrained('kabupaten');
            $table->string('nama', 150);
            $table->decimal('lat', 10, 8);
            $table->decimal('long_decimal', 11, 8);
            $table->unsignedInteger('populasi')->default(0);
            $table->unsignedInteger('korban_selamat')->default(0);
            $table->unsignedInteger('jumlah_orang_sakit')->default(0);
            $table->decimal('persentase_infrastruktur_rusak', 5, 2)->default(0.00);
            $table->boolean('status_isolasi')->default(false);
            $table->boolean('status_aman')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desa');
    }
};
