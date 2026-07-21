<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('bpjs_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->unique()->constrained('clinics')->cascadeOnDelete();
            $table->string('cons_id');
            $table->text('secret_key');
            $table->text('user_key_antrol');
            $table->text('user_key_pcare');
            $table->string('kode_aplikasi', 10)->default('095');
            $table->string('username');
            $table->text('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('bpjs_configurations');
    }
};
