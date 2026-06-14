<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demand_kebutuhan', function (Blueprint $table) {
            $table->id();
            $table->binary('kode_batch')->unique();
            $table->foreignId('id_desa')->constrained('desa');
            $table->foreignId('id_barang')->constrained('master_bantuan');
            $table->unsignedInteger('kuantitas');
            $table->decimal('urgency_score', 6, 4)->nullable();
            $table->decimal('target_deadline_jam', 6, 2)->nullable();
            $table->enum('status', ['Draft', 'Queued', 'Manifested', 'Delivered', 'Fulfilled'])->default('Draft');
            $table->timestamps();

            $table->index(['id_desa', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_kebutuhan');
    }
};
