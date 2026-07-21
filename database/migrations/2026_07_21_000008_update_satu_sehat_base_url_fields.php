<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->table('satu_sehat_base_url_configurations', function (Blueprint $table) {
            $table->text('auth_url')->nullable()->after('clinic_id');
            $table->text('fhir_url')->nullable()->after('auth_url');
            $table->dropColumn('base_url_satu_sehat');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('satu_sehat_base_url_configurations', function (Blueprint $table) {
            $table->text('base_url_satu_sehat')->nullable()->after('clinic_id');
            $table->dropColumn(['auth_url', 'fhir_url']);
        });
    }
};
