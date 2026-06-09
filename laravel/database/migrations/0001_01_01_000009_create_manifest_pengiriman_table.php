<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manifest_pengiriman', function (Blueprint $table) {
            $table->id();
            $table->binary('kode_manifest')->unique();
            $table->foreignId('id_pusat')->constrained('pusat_distribusi');
            $table->foreignId('id_armada')->constrained('armada_kendaraan');
            $table->enum('status', ['In-Transit', 'Delivered'])->default('In-Transit');
            $table->dateTime('waktu_berangkat');
            $table->dateTime('waktu_tiba')->nullable();
            $table->timestamps();

            $table->index(['id_pusat', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manifest_pengiriman');
    }
};
