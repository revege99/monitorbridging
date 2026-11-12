<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\ClinicDatabase;
use App\Services\ClinicContext;
use App\Services\LocalClinic;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class LocalClinicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.central' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('central');
        Schema::connection('central')->create('application_profiles', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active');
        });
        Schema::connection('central')->create('application_database_configurations', function (Blueprint $table) {
            $table->id();
            $table->text('host')->default('127.0.0.1');
            $table->integer('port')->default(3306);
            $table->text('database_name')->default('sik');
        });
        DB::connection('central')->table('application_profiles')->insert(['id' => 1, 'is_active' => true]);
        DB::connection('central')->table('application_database_configurations')->insert(['id' => 1]);
    }

    public function test_profile_resolves_without_installation_or_clinic_id_or_bpjs_tables(): void
    {
        $context = app(LocalClinic::class)->resolveForPdf();
        $this->assertSame(1, $context->clinic->id);
        $this->assertNotNull($context->clinic->database);
        $this->assertFalse(Schema::connection('central')->hasTable('installation_settings'));
    }

    public function test_disabled_profile_is_rejected(): void
    {
        DB::connection('central')->table('application_profiles')->update(['is_active' => false]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Profil klinik aplikasi sedang nonaktif.');
        app(LocalClinic::class)->resolveForPdf();
    }

    public function test_missing_connection_is_rejected(): void
    {
        DB::connection('central')->table('application_database_configurations')->delete();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Konfigurasi database SIMRS belum tersedia.');
        app(LocalClinic::class)->resolveForPdf();
    }

    public function test_connecting_is_lazy_and_unchanged_configuration_does_not_purge(): void
    {
        $database = new ClinicDatabase(['driver' => 'mariadb', 'host' => '127.0.0.1', 'port' => 3306,
            'database_name' => 'sik', 'username' => 'test', 'password' => 'secret', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci']);
        $clinic = new Clinic;
        $clinic->setRelation('database', $database);
        $context = new ClinicContext($clinic);
        DB::shouldReceive('purge')->once()->with('clinic');
        DB::shouldNotReceive('connection');
        $resolver = new LocalClinic;
        $resolver->configureClinicDatabase($context);
        $resolver->configureClinicDatabase($context);
        $this->assertSame('127.0.0.1', config('database.connections.clinic.host'));
    }
}
