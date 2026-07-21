<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'central';

    public function up(): void
    {
        foreach (['base_url_configurations', 'satu_sehat_base_url_configurations'] as $table) {
            $firstId = DB::connection('central')->table($table)->min('id');
            if ($firstId) DB::connection('central')->table($table)->where('id', '<>', $firstId)->delete();
            Schema::connection('central')->table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('clinic_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['base_url_configurations', 'satu_sehat_base_url_configurations'] as $table) {
            Schema::connection('central')->table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            });
        }
    }
};
