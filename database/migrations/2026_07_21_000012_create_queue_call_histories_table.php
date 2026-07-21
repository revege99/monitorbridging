<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('clinic')->create('riwayat_panggilan_poli', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('no_rawat', 30)->index();
            $table->string('no_antrean', 30)->nullable();
            $table->string('nama_pasien')->nullable();
            $table->string('nama_dokter')->nullable();
            $table->string('nama_poli')->nullable();
            $table->string('sumber_panggilan', 30)->default('panggil');
            $table->dateTime('waktu_panggil')->index();
            $table->index(['no_rawat', 'waktu_panggil']);
        });
    }

    public function down(): void
    {
        Schema::connection('clinic')->dropIfExists('riwayat_panggilan_poli');
    }
};
