<?php

namespace Tests\Feature\Api\Pdf;

use App\Models\Clinic;
use App\Services\ClinicContext;
use App\Services\LocalClinic;
use App\Services\Pdf\HealthCertificateService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class HealthCertificateTest extends TestCase
{
    private const NO_RAWAT = '2026/11/16/000001';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.clinic' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'services.khanza.integration_token' => 'pdf-test-token']);
        DB::purge('clinic');
        foreach ([
            'surat_keterangan_sehat' => ['no_surat', 'no_rawat', 'tanggalsurat', 'berat', 'tinggi', 'tensi', 'suhu', 'butawarna', 'keperluan', 'kesimpulan'],
            'reg_periksa' => ['no_rawat', 'no_rkm_medis', 'kd_dokter'], 'pasien' => ['no_rkm_medis', 'nm_pasien', 'tmp_lahir', 'tgl_lahir', 'jk', 'alamat', 'kelurahanpj', 'kecamatanpj', 'kabupatenpj'],
            'dokter' => ['kd_dokter', 'nm_dokter'], 'setting' => ['nama_instansi', 'alamat_instansi', 'kabupaten', 'propinsi', 'kontak', 'email', 'aktifkan'],
        ] as $table => $columns) {
            Schema::connection('clinic')->create($table, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
            });
        }
        $db = DB::connection('clinic');
        $db->table('setting')->insert(['nama_instansi' => 'KLINIK CONTOH', 'alamat_instansi' => 'Jl. Contoh No. 1', 'kabupaten' => 'Sidikalang', 'propinsi' => 'Sumatera Utara', 'kontak' => '0812', 'email' => 'klinik@example.test', 'aktifkan' => 'Yes']);
        $db->table('pasien')->insert(['no_rkm_medis' => '000001', 'nm_pasien' => 'ENDA', 'tmp_lahir' => 'Pakpak Bharat', 'tgl_lahir' => '2006-05-04', 'jk' => 'P', 'alamat' => 'Resdes', 'kelurahanpj' => 'Siempat Rube IV', 'kecamatanpj' => 'Siempat Rube', 'kabupatenpj' => 'Pakpak Bharat']);
        $db->table('dokter')->insert(['kd_dokter' => 'D001', 'nm_dokter' => 'dr. Contoh']);
        $db->table('reg_periksa')->insert(['no_rawat' => self::NO_RAWAT, 'no_rkm_medis' => '000001', 'kd_dokter' => 'D001']);
        $db->table('surat_keterangan_sehat')->insert(['no_surat' => 'SKS-001', 'no_rawat' => self::NO_RAWAT, 'tanggalsurat' => '2026-11-16', 'berat' => '56', 'tinggi' => '156', 'tensi' => '100/60', 'suhu' => '36', 'butawarna' => 'Tidak', 'keperluan' => 'TESTING', 'kesimpulan' => 'Sehat']);
        $this->mock(LocalClinic::class, function (MockInterface $mock) {
            $context = new ClinicContext(new Clinic(['legal_name' => 'YAYASAN CONTOH']));
            $mock->shouldReceive('resolveForPdf')->andReturn($context);
            $mock->shouldReceive('configureClinicDatabase')->with($context)->andReturnNull();
        });
    }

    public function test_builds_health_certificate_from_simrs_tables(): void
    {
        $data = app(HealthCertificateService::class)->data(self::NO_RAWAT, null, new Clinic(['legal_name' => 'YAYASAN CONTOH']));
        $this->assertSame('SKS-001', $data['letter_number']);
        $this->assertSame('Pakpak Bharat, 4 Mei 2006', $data['birth']);
        $this->assertSame('156 cm', $data['height']);
        $this->assertSame('SEHAT', strtoupper($data['conclusion']));
    }

    public function test_returns_single_page_pdf(): void
    {
        $response = $this->withHeader('X-Integration-Token', 'pdf-test-token')->post('/api/integration/pdf/bpjs/suratKeteranganSehat', ['no_rawat' => self::NO_RAWAT])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()));
    }
}
