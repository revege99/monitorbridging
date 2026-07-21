<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('queue_display_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->string('queue_number', 30);
            $table->string('patient_name');
            $table->string('doctor_name')->nullable();
            $table->string('clinic_name')->nullable();
            $table->timestamp('called_at');
            $table->timestamps();
            $table->index(['clinic_id', 'called_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('queue_display_calls');
    }
};
