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
        Schema::create('corporate_products', function (Blueprint $table) {
            $table->id();
            $table->string('nama_produk');
            $table->string('kategori_perusahaan');
            $table->text('list_produk')->nullable();
            $table->string('jenis_pembiayaan')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('plafon')->nullable();
            $table->json('requirements')->nullable();
            $table->json('benefits')->nullable();
            $table->string('interest_rate')->nullable();
            $table->json('terms_and_conditions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporate_products');
    }
};
