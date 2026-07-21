<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('satu_sehat_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->unique()->constrained('clinics')->cascadeOnDelete();
            $table->string('client_id');
            $table->text('client_secret');
            $table->string('organization_id');
            $table->string('location_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('satu_sehat_configurations');
    }
};
