<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rute', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_titik_asal');
            $table->unsignedBigInteger('id_titik_tujuan');
            $table->float('jarak_km');
            $table->boolean('status_akses_terbuka')->default(true);
            $table->timestamps();
            $table->unique(['id_titik_asal', 'id_titik_tujuan'], 'idx_asal_tujuan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rute');
    }
};
