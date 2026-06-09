<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_bantuan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->enum('kategori', ['Medis', 'Air', 'Ransum', 'Tenda']);
            $table->decimal('berat_kg', 8, 2);
            $table->decimal('volume_m3', 8, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_bantuan');
    }
};
