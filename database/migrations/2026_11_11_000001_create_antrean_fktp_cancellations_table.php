<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('antrean_fktp_cancellations', function (Blueprint $table) {
            $table->string('no_rawat', 17)->index();
            $table->string('status', 20);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('antrean_fktp_cancellations');
    }
};
