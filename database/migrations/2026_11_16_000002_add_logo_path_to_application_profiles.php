<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasColumn('application_profiles', 'logo_path')) {
            $schema->table('application_profiles', function (Blueprint $table) {
                $table->string('logo_path')->nullable()->after('bpjs_kantor_cabang');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasColumn('application_profiles', 'logo_path')) {
            $schema->table('application_profiles', fn (Blueprint $table) => $table->dropColumn('logo_path'));
        }
    }
};
