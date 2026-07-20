<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection('central')->create('clinic_databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->unique()->constrained('clinics')->cascadeOnDelete();
            $table->string('connection_name')->default('SIMRS');
            $table->string('driver', 20)->default('mariadb');
            $table->string('host')->default('127.0.0.1');
            $table->unsignedInteger('port')->default(3306);
            $table->string('database_name');
            $table->string('username');
            $table->text('password')->nullable();
            $table->string('charset', 20)->default('utf8mb4');
            $table->string('collation', 50)->default('utf8mb4_unicode_ci');
            $table->timestamps();
        });

        Schema::connection('central')->table('users', function (Blueprint $table) {
            $table->foreign('clinic_id')->references('id')->on('clinics')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('users', fn (Blueprint $table) => $table->dropForeign(['clinic_id']));
        Schema::connection('central')->dropIfExists('clinic_databases');
        Schema::connection('central')->dropIfExists('clinics');
    }
};
