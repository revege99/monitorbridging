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

        if (! $schema->hasColumn('application_profiles', 'bpjs_divisi_regional')) {
            $schema->table('application_profiles', function (Blueprint $table) {
                $table->string('bpjs_divisi_regional', 150)->nullable()->after('postal_code');
            });
        }

        if (! $schema->hasColumn('application_profiles', 'bpjs_kantor_cabang')) {
            $schema->table('application_profiles', function (Blueprint $table) {
                $table->string('bpjs_kantor_cabang', 150)->nullable()->after('bpjs_divisi_regional');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $columns = array_values(array_filter([
            $schema->hasColumn('application_profiles', 'bpjs_divisi_regional') ? 'bpjs_divisi_regional' : null,
            $schema->hasColumn('application_profiles', 'bpjs_kantor_cabang') ? 'bpjs_kantor_cabang' : null,
        ]));

        if ($columns !== []) {
            $schema->table('application_profiles', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
