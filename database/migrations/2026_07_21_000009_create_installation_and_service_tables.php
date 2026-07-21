<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'central';
    public function up(): void
    {
        Schema::connection('central')->create('installation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->unique()->constrained('clinics')->cascadeOnDelete();
            $table->uuid('installation_uuid')->unique();
            $table->boolean('is_activated')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::connection('central')->create('service_runs', function (Blueprint $table) {
            $table->id(); $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->string('service_name'); $table->string('status', 30)->default('running');
            $table->timestamp('started_at'); $table->timestamp('finished_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->unsignedInteger('processed')->default(0); $table->unsignedInteger('succeeded')->default(0); $table->unsignedInteger('failed')->default(0);
            $table->text('message')->nullable(); $table->timestamps();
            $table->index(['clinic_id', 'service_name', 'status']);
        });
        Schema::connection('central')->create('service_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('service_run_id')->nullable()->constrained('service_runs')->nullOnDelete();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->string('service_name'); $table->string('level', 20)->default('info'); $table->string('status', 30)->nullable();
            $table->string('reference')->nullable(); $table->text('message'); $table->unsignedSmallInteger('response_code')->nullable();
            $table->unsignedInteger('duration_ms')->nullable(); $table->json('context')->nullable(); $table->timestamps();
            $table->index(['clinic_id', 'service_name', 'id']);
        });
    }
    public function down(): void
    {
        Schema::connection('central')->dropIfExists('service_logs');
        Schema::connection('central')->dropIfExists('service_runs');
        Schema::connection('central')->dropIfExists('installation_settings');
    }
};
