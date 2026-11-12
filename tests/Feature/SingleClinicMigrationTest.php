<?php

namespace Tests\Feature;

use App\Models\BpjsConfiguration;
use App\Models\ClinicDatabase;
use App\Services\LocalClinic;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SingleClinicMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.central' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('central');
        Schema::connection('central')->create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->boolean('is_active');
        });
        Schema::connection('central')->create('installation_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('clinic_id');
            $table->boolean('is_activated');
        });
        Schema::connection('central')->create('clinic_databases', function (Blueprint $table) {
            $table->id();
            $table->integer('clinic_id');
            $table->string('database_name');
            $table->string('username');
            $table->text('password');
        });
        Schema::connection('central')->create('bpjs_configurations', function (Blueprint $table) {
            $table->id();
            $table->integer('clinic_id');
            $table->string('cons_id');
            $table->string('username');
            foreach (['secret_key', 'user_key_antrol', 'user_key_pcare', 'password'] as $field) {
                $table->text($field);
            }
        });
        Schema::connection('central')->create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id');
        });
        DB::connection('central')->table('clinics')->insert([
            ['id' => 7, 'code' => 'A', 'name' => 'Clinic A', 'is_active' => true],
            ['id' => 9, 'code' => 'B', 'name' => 'Clinic B', 'is_active' => true],
        ]);
        DB::connection('central')->table('installation_settings')->insert(['clinic_id' => 9, 'is_activated' => true]);
        DB::connection('central')->table('clinic_databases')->insert([
            'clinic_id' => 9, 'database_name' => 'sik_b', 'username' => 'test', 'password' => Crypt::encryptString('db-secret'),
        ]);
        DB::connection('central')->table('bpjs_configurations')->insert([
            'clinic_id' => 9, 'cons_id' => 'consumer-b', 'username' => 'test',
            'secret_key' => Crypt::encryptString('bpjs-secret'), 'password' => Crypt::encryptString('bpjs-password'),
            'user_key_antrol' => Crypt::encryptString('antrol'), 'user_key_pcare' => Crypt::encryptString('pcare'),
        ]);
    }

    private function migrateSingleClinic(): void
    {
        (require database_path('migrations/2026_11_12_000001_use_single_clinic_application_configuration.php'))->up();
    }

    public function test_migration_preserves_sources_and_copies_only_active_clinic_without_foreign_ids(): void
    {
        $this->migrateSingleClinic();
        $context = app(LocalClinic::class)->resolveForPdf();
        $this->assertSame('Clinic B', $context->clinic->name);
        $this->assertSame('sik_b', $context->clinic->database->database_name);
        $this->assertSame('db-secret', ClinicDatabase::first()->password);
        $this->assertSame('bpjs-secret', BpjsConfiguration::first()->secret_key);
        foreach (['application_profiles', 'application_database_configurations', 'application_bpjs_configurations'] as $table) {
            $this->assertDatabaseCount($table, 1, 'central');
            $this->assertFalse(Schema::connection('central')->hasColumn($table, 'clinic_id'));
        }
        $this->assertDatabaseCount('clinics', 2, 'central');
        DB::connection('central')->table('service_logs')->insert(['clinic_id' => null]);
        $this->assertDatabaseCount('service_logs', 1, 'central');
        DB::connection('central')->table('installation_settings')->delete();
        $this->assertSame('Clinic B', app(LocalClinic::class)->resolveForPdf()->clinic->name);
    }

    public function test_ambiguous_sources_abort_before_creating_any_new_configuration(): void
    {
        DB::connection('central')->table('installation_settings')->delete();
        try {
            $this->migrateSingleClinic();
            $this->fail('Ambiguous source must not be selected automatically.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('beberapa profil', $exception->getMessage());
        }
        $this->assertFalse(Schema::connection('central')->hasTable('application_profiles'));
        $this->assertDatabaseCount('clinics', 2, 'central');
    }
}
