<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        $db = DB::connection('central');
        $schema = Schema::connection('central');
        $profile = null;
        if ($schema->hasTable('clinics')) {
            $active = $schema->hasTable('installation_settings')
                ? $db->table('installation_settings')->where('is_activated', true)->pluck('clinic_id')->unique() : collect();
            if ($active->count() > 1) {
                throw new RuntimeException('Lebih dari satu instalasi aktif. Tentukan satu klinik sumber sebelum migrasi.');
            }
            if ($active->count() === 1) {
                $profile = $db->table('clinics')->where('id', $active->first())->first();
                if (! $profile) {
                    throw new RuntimeException('Profil instalasi lama tidak ditemukan. Perbaiki data sumber sebelum migrasi.');
                }
            } elseif ($db->table('clinics')->count() > 1) {
                throw new RuntimeException('Terdapat beberapa profil klinik tanpa instalasi aktif. Pilih sumber menggunakan aktivasi versi lama sebelum migrasi; tidak ada data yang diubah.');
            } else {
                $profile = $db->table('clinics')->first();
            }
        }

        if (! $schema->hasTable('application_profiles')) {
            $schema->create('application_profiles', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary()->default(1);
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
        }
        if (! $schema->hasTable('application_database_configurations')) {
            $schema->create('application_database_configurations', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary()->default(1);
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
        }
        if (! $schema->hasTable('application_bpjs_configurations')) {
            $schema->create('application_bpjs_configurations', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary()->default(1);
                $table->string('cons_id');
                $table->text('secret_key');
                $table->text('user_key_antrol');
                $table->text('user_key_pcare');
                $table->string('kode_aplikasi', 10)->default('095');
                $table->string('username');
                $table->text('password');
                $table->timestamps();
            });
        }
        if ($profile) {
            $this->copy('application_profiles', $profile);
            foreach (['clinic_databases' => 'application_database_configurations', 'bpjs_configurations' => 'application_bpjs_configurations'] as $source => $target) {
                if ($schema->hasTable($source)) {
                    $row = $db->table($source)->where('clinic_id', $profile->id)->first();
                    if ($row) {
                        $this->copy($target, $row);
                    }
                }
            }
        }
        // Riwayat lama dipertahankan; penulisan baru tidak membutuhkan clinic_id.
        foreach (['service_runs', 'service_logs', 'queue_display_calls'] as $table) {
            if ($schema->hasColumn($table, 'clinic_id')) {
                $schema->table($table, fn (Blueprint $blueprint) => $blueprint->unsignedBigInteger('clinic_id')->nullable()->change());
            }
        }
    }

    private function copy(string $table, object $row): void
    {
        $db = DB::connection('central');
        if ($db->table($table)->exists()) {
            return;
        }
        $data = array_intersect_key((array) $row, array_flip(Schema::connection('central')->getColumnListing($table)));
        $data['id'] = 1;
        // Query builder menyalin ciphertext apa adanya, tanpa mengenkripsi dua kali.
        $db->table($table)->insert($data);
    }

    public function down(): void
    {
        throw new RuntimeException('Migrasi satu klinik tidak di-rollback otomatis agar konfigurasi baru tidak hilang. Tabel konfigurasi lama tetap tersedia untuk pemulihan terencana.');
    }
};
