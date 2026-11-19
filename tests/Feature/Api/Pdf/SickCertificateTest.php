<?php

namespace Tests\Feature\Api\Pdf;

use App\Models\Clinic;
use App\Services\ClinicContext;
use App\Services\LocalClinic;
use App\Services\Pdf\SickCertificateService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class SickCertificateTest extends TestCase
{
    private const NO_RAWAT = '2026/11/19/000001';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.clinic' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'services.khanza.integration_token' => 'pdf-test-token']);
        DB::purge('clinic');
        foreach ([
            'suratsakit' => ['no_surat', 'no_rawat', 'tanggalawal', 'tanggalakhir', 'lamasakit'],
            'reg_periksa' => ['no_rawat', 'no_rkm_medis', 'kd_dokter', 'umurdaftar', 'sttsumur'],
            'pasien' => ['no_rkm_medis', 'nm_pasien', 'jk', 'pekerjaan', 'alamat', 'kelurahanpj', 'kecamatanpj', 'kabupatenpj', 'propinsipj'],
            'dokter' => ['kd_dokter', 'nm_dokter'],
            'setting' => ['yayasan', 'nama_instansi', 'alamat_instansi', 'kabupaten', 'propinsi', 'kontak', 'email', 'aktifkan'],
        ] as $table => $columns) {
            Schema::connection('clinic')->create($table, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
            });
        }
        $db = DB::connection('clinic');
        $db->table('setting')->insert(['yayasan' => 'YAYASAN CONTOH', 'nama_instansi' => 'KLINIK CONTOH', 'alamat_instansi' => 'Jl. Contoh No. 1', 'kabupaten' => 'Sidikalang', 'propinsi' => 'Sumatera Utara', 'kontak' => '0812', 'email' => 'klinik@example.test', 'aktifkan' => 'Yes']);
        $db->table('pasien')->insert(['no_rkm_medis' => '000001', 'nm_pasien' => 'SUSI CONTOH', 'jk' => 'P', 'pekerjaan' => 'PNS', 'alamat' => 'Sitinjo', 'kelurahanpj' => 'Sitinjo II', 'kecamatanpj' => 'Sitinjo', 'kabupatenpj' => 'Dairi', 'propinsipj' => 'Sumatera Utara']);
        $db->table('dokter')->insert(['kd_dokter' => 'D001', 'nm_dokter' => 'dr. Contoh']);
        $db->table('reg_periksa')->insert(['no_rawat' => self::NO_RAWAT, 'no_rkm_medis' => '000001', 'kd_dokter' => 'D001', 'umurdaftar' => '59', 'sttsumur' => 'Th']);
        $db->table('suratsakit')->insert(['no_surat' => '814/KSM/XI/2026', 'no_rawat' => self::NO_RAWAT, 'tanggalawal' => '2026-11-19', 'tanggalakhir' => '2026-11-20', 'lamasakit' => '2 (Dua)']);
        $this->mock(LocalClinic::class, function (MockInterface $mock) {
            $context = new ClinicContext(new Clinic);
            $mock->shouldReceive('resolveForPdf')->andReturn($context);
            $mock->shouldReceive('configureClinicDatabase')->with($context)->andReturnNull();
        });
    }

    public function test_builds_sick_certificate_from_suratsakit(): void
    {
        $data = app(SickCertificateService::class)->data(self::NO_RAWAT, null, new Clinic);
        $this->assertSame('814/KSM/XI/2026', $data['letter_number']);
        $this->assertSame('59 Tahun', $data['age']);
        $this->assertSame('2 (Dua)', $data['duration']);
        $this->assertSame('19 November 2026', $data['start_date']);
        $this->assertSame('20 November 2026', $data['end_date']);
    }

    public function test_returns_single_page_pdf(): void
    {
        $response = $this->withHeader('X-Integration-Token', 'pdf-test-token')->post('/api/integration/pdf/bpjs/suratKeteranganSakit', ['no_rawat' => self::NO_RAWAT])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()));
    }
}
