<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->table('base_url_configurations', function (Blueprint $table) {
            $table->text('base_url_antrean_batal')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('base_url_configurations', function (Blueprint $table) {
            $table->dropColumn('base_url_antrean_batal');
        });
    }
};
