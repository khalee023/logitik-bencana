<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_pusat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pusat');
            $table->unsignedBigInteger('id_barang');
            $table->integer('total_kuantitas')->default(0);
            $table->timestamps();

            $table->unique(['id_pusat', 'id_barang']);
            $table->foreign('id_pusat')->references('id')->on('pusat_distribusi');
            $table->foreign('id_barang')->references('id')->on('master_bantuan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_pusat');
    }
};
