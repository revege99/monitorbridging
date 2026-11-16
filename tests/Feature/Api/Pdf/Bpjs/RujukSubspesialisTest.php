<?php

namespace Tests\Feature\Api\Pdf\Bpjs;

use App\Models\Clinic;
use App\Services\ClinicContext;
use App\Services\LocalClinic;
use App\Services\Pdf\Bpjs\RujukSubspesialisService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class RujukSubspesialisTest extends TestCase
{
    private const NO_RAWAT = '2026/11/16/000001';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.clinic' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('clinic');

        foreach ([
            'pcare_rujuk_subspesialis' => ['no_rawat', 'no_rkm_medis', 'noKunjungan', 'noKartu', 'tglDaftar', 'nmSubSpesialis', 'nmPPK', 'nmDiag1', 'alasanTACC', 'jadwalFaskes'],
            'pasien' => ['no_rkm_medis', 'nm_pasien', 'tgl_lahir', 'jk'],
            'reg_periksa' => ['no_rawat', 'kd_dokter', 'umurdaftar', 'sttsumur'],
            'dokter' => ['kd_dokter', 'nm_dokter'],
            'setting' => ['kode_ppk', 'nama_instansi', 'kabupaten', 'aktifkan'],
        ] as $table => $columns) {
            Schema::connection('clinic')->create($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->string($column)->nullable();
                }
            });
        }

        $db = DB::connection('clinic');
        $db->table('setting')->insert(['kode_ppk' => '0030B011', 'nama_instansi' => 'Klinik Pratama Contoh', 'kabupaten' => 'Sidikalang', 'aktifkan' => 'Yes']);
        $db->table('pasien')->insert(['no_rkm_medis' => '000001', 'nm_pasien' => 'PASIEN CONTOH', 'tgl_lahir' => '1954-04-14', 'jk' => 'L']);
        $db->table('dokter')->insert(['kd_dokter' => 'D001', 'nm_dokter' => 'dr. Contoh']);
        $db->table('reg_periksa')->insert(['no_rawat' => self::NO_RAWAT, 'kd_dokter' => 'D001', 'umurdaftar' => '72', 'sttsumur' => 'Th']);
        $db->table('pcare_rujuk_subspesialis')->insert([
            'no_rawat' => self::NO_RAWAT, 'no_rkm_medis' => '000001', 'noKunjungan' => '0030B0111126Y000364',
            'noKartu' => '0002923617069', 'tglDaftar' => '2026-11-16', 'nmSubSpesialis' => 'PARU',
            'nmPPK' => 'RSU CONTOH', 'nmDiag1' => 'Diagnosis contoh', 'alasanTACC' => '', 'jadwalFaskes' => 'Senin : 15:30 - 17:30',
        ]);

        config(['services.khanza.integration_token' => 'pdf-test-token', 'app.debug' => false]);
        $this->mock(LocalClinic::class, function (MockInterface $mock) {
            $profile = new Clinic([
                'bpjs_divisi_regional' => 'REGIONAL I-SUMATERA UTARA',
                'bpjs_kantor_cabang' => 'KABANJAHE',
            ]);
            $context = new ClinicContext($profile);
            $mock->shouldReceive('resolveForPdf')->andReturn($context);
            $mock->shouldReceive('configureClinicDatabase')->with($context)->andReturnNull();
        });
    }

    public function test_uses_bpjs_referral_data_and_formats_facility_like_the_official_document(): void
    {
        $profile = new Clinic([
            'bpjs_divisi_regional' => 'REGIONAL I-SUMATERA UTARA',
            'bpjs_kantor_cabang' => 'KABANJAHE',
        ]);
        $data = app(RujukSubspesialisService::class)->data(self::NO_RAWAT, $profile);

        $this->assertSame('0030B0111126Y000364', $data['referral_number']);
        $this->assertSame('Klinik Pratama Contoh(0030B011)', $data['facility']);
        $this->assertSame('Sidikalang', $data['district']);
        $this->assertSame('14 April 1954', $data['birth_date']);
        $this->assertSame('13 Februari 2027', $data['valid_until']);
        $this->assertSame('REGIONAL I-SUMATERA UTARA', $data['regional']);
        $this->assertSame('KABANJAHE', $data['branch_office']);
    }

    public function test_returns_a_single_page_referral_pdf(): void
    {
        $response = $this->withHeader('X-Integration-Token', 'pdf-test-token')
            ->post('/api/integration/pdf/bpjs/rujukSubspesialis', ['no_rawat' => self::NO_RAWAT])
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('inline;', $response->headers->get('Content-Disposition'));
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()));
    }
}
