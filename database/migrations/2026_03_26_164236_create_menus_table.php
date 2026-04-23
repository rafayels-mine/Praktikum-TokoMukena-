<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_admin')->default(false); // Admin status (0 = bukan admin, 1 = admin)
            $table->string('kode_menu')->unique(); 
            $table->string('nama_menu');
            $table->decimal('harga', 10, 2);
            $table->enum('kategori', ['Makanan', 'Minuman']);
            $table->enum('status', ['Tersedia', 'Tidak Tersedia'])->default('Tersedia');
            $table->string('gambar');
            $table->string('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
