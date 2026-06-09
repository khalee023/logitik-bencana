<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('armada_kendaraan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pusat')->constrained('pusat_distribusi');
            $table->string('plat_nomor', 20)->unique();
            $table->decimal('max_berat_kg', 10, 2);
            $table->decimal('max_vol_m3', 8, 4);
            $table->enum('status', ['Available', 'In-Transit', 'Maintenance'])->default('Available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('armada_kendaraan');
    }
};
