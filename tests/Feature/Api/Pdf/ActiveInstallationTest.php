<?php

namespace Tests\Feature\Api\Pdf;

use App\Services\ActiveInstallation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ActiveInstallationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.central' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('central');
        Schema::connection('central')->create('installation_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('clinic_id');
            $table->boolean('is_activated');
        });
        Schema::connection('central')->create('clinics', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active');
        });
        Schema::connection('central')->create('clinic_databases', function (Blueprint $table) {
            $table->id();
            $table->integer('clinic_id');
        });
        DB::connection('central')->table('installation_settings')->insert([
            'clinic_id' => 1, 'is_activated' => true,
        ]);
        DB::connection('central')->table('clinics')->insert(['id' => 1, 'is_active' => true]);
        DB::connection('central')->table('clinic_databases')->insert(['clinic_id' => 1]);
    }

    public function test_pdf_resolves_active_clinic_without_bpjs_tables_or_credentials(): void
    {
        $installation = app(ActiveInstallation::class)->resolveForPdf();

        $this->assertSame(1, $installation->clinic->id);
        $this->assertNotNull($installation->clinic->database);
    }

    public function test_inactive_clinic_is_rejected(): void
    {
        DB::connection('central')->table('clinics')->update(['is_active' => false]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Klinik yang diaktifkan sedang nonaktif.');

        app(ActiveInstallation::class)->resolveForPdf();
    }

    public function test_clinic_without_database_configuration_is_rejected(): void
    {
        DB::connection('central')->table('clinic_databases')->delete();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Konfigurasi database SIMRS belum tersedia.');

        app(ActiveInstallation::class)->resolveForPdf();
    }
}
