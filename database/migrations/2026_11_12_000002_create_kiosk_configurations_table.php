<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('kiosk_configurations', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary()->default(1);
            $table->unsignedTinyInteger('rm_digits')->default(6);
            $table->boolean('rm_use_separator')->default(true);
            $table->string('rm_separator', 1)->default('-');
            $table->string('rm_groups', 30)->default('2-2-2');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('kiosk_configurations');
    }
};
