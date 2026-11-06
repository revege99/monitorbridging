<?php

namespace Tests\Feature\Api\Pdf\Bpjs;

use App\Models\InstallationSetting;
use App\Services\ActiveInstallation;
use App\Services\Pdf\Bpjs\SppFktpService;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\Support\SppFktpFixture as Fixture;
use Tests\TestCase;

class SppFktpTest extends TestCase
{
    private const URL = '/api/integration/pdf/bpjs/sppFKTP';

    protected function setUp(): void
    {
        parent::setUp();
        Fixture::seed();
        config(['services.khanza.integration_token' => 'pdf-test-token', 'app.debug' => false]);
        $this->mock(ActiveInstallation::class, function (MockInterface $mock) {
            $installation = new InstallationSetting;
            $mock->shouldReceive('resolveForPdf')->andReturn($installation);
            $mock->shouldReceive('configureClinicDatabase')->with($installation)->andReturnNull();
        });
    }

    public function test_endpoint_requires_header_token_and_valid_care_number(): void
    {
        $this->postJson(self::URL, ['no_rawat' => Fixture::NO_RAWAT])->assertUnauthorized();
        $this->postJson(self::URL.'?token=pdf-test-token')->assertUnauthorized();
        $this->withHeader('X-Integration-Token', 'pdf-test-token')
            ->postJson(self::URL)->assertUnprocessable()->assertJsonValidationErrors('no_rawat');
    }

    public function test_returns_inline_single_page_pdf_from_json_and_form(): void
    {
        $this->withHeader('X-Integration-Token', 'pdf-test-token');
        foreach (['postJson', 'post'] as $method) {
            $response = $this->{$method}(self::URL, ['no_rawat' => Fixture::NO_RAWAT])
                ->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->assertStringContainsString('inline;', $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('sppFKTP-2026-11-02-000001.pdf', $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()));
        }
    }

    public function test_data_comes_from_matching_visit_and_setting_in_clinic_database(): void
    {
        // Default sengaja bukan clinic: semua query PDF harus eksplisit memilih clinic.
        DB::setDefaultConnection('sqlite');
        DB::connection('clinic')->table('pcare_tindakan_ralan_diberikan')->insert([
            'no_rawat' => '2026/11/02/000002', 'noKunjungan' => 'KUNJUNGAN-LAIN',
            'kd_jenis_prw' => 'LAIN', 'tgl_perawatan' => '2026-11-02', 'jam' => '11:00:00',
        ]);
        $data = app(SppFktpService::class)->data(Fixture::NO_RAWAT);
        $this->assertSame('00000000 - KLINIK CONTOH', $data['facility']);
        $this->assertSame('000001', $data['medical_record']);
        $this->assertSame('0000000000001', $data['card_number']);
        $this->assertSame('000000001126P000001', $data['visit_number']);
        $this->assertSame('02/11/2026', $data['service_date']);
        $this->assertSame('29 tahun 3 bulan 26 hari', $data['age']);
        $this->assertSame('Perempuan', $data['sex']);
        $this->assertSame('RJTP', $data['service_type']);
        $this->assertSame(['Pelayanan ANC'], $data['services']);
    }

    public function test_unknown_visit_returns_json_404(): void
    {
        $this->withHeader('X-Integration-Token', 'pdf-test-token')
            ->postJson(self::URL, ['no_rawat' => '2026/11/02/999999'])
            ->assertNotFound()->assertJsonPath('message', 'Data kunjungan atau pasien tidak ditemukan.');
    }

    public function test_missing_optional_data_uses_honest_fallbacks(): void
    {
        $db = DB::connection('clinic');
        $db->table('pcare_pendaftaran')->delete();
        $db->table('pcare_tindakan_ralan_diberikan')->delete();
        $db->table('pasien')->update(['tgl_lahir' => '0000-00-00', 'no_tlp' => null]);
        $data = app(SppFktpService::class)->data(Fixture::NO_RAWAT);
        $this->assertSame('0000000000000', $data['card_number']);
        $this->assertSame('01/11/2026', $data['service_date']);
        $this->assertSame('-', $data['age']);
        $this->assertSame('-', $data['phone']);
        $this->assertSame('-', $data['visit_number']);
        $this->assertSame([], $data['services']);
    }

    public function test_conflicting_visit_numbers_are_rejected_before_printing(): void
    {
        DB::connection('clinic')->table('pcare_tindakan_ralan_diberikan')->insert([
            'no_rawat' => Fixture::NO_RAWAT, 'noKunjungan' => 'NOMOR-BERBEDA',
            'kd_jenis_prw' => 'LAIN', 'tgl_perawatan' => '2026-11-02', 'jam' => '11:00:00',
        ]);
        $this->withHeader('X-Integration-Token', 'pdf-test-token')
            ->postJson(self::URL, ['no_rawat' => Fixture::NO_RAWAT])
            ->assertUnprocessable()->assertJsonValidationErrors('no_rawat');
    }

    public function test_missing_setting_returns_service_unavailable(): void
    {
        DB::connection('clinic')->table('setting')->delete();
        $this->withHeader('X-Integration-Token', 'pdf-test-token')
            ->postJson(self::URL, ['no_rawat' => Fixture::NO_RAWAT])->assertStatus(503);
    }

    public function test_database_error_does_not_expose_sql_even_when_debug_is_enabled(): void
    {
        config(['app.debug' => true]);
        DB::connection('clinic')->statement('DROP TABLE setting');
        $response = $this->withHeader('X-Integration-Token', 'pdf-test-token')
            ->postJson(self::URL, ['no_rawat' => Fixture::NO_RAWAT])
            ->assertStatus(500)->assertJsonPath('message', 'PDF gagal dibuat. Periksa konfigurasi dan data klinik.');
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
    }

    public function test_long_service_list_can_continue_to_multiple_pages(): void
    {
        $db = DB::connection('clinic');
        for ($i = 1; $i <= 55; $i++) {
            $code = 'T'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $db->table('jns_perawatan')->insert([
                'kd_jenis_prw' => $code, 'nm_perawatan' => 'Pelayanan contoh '.$i,
            ]);
            $db->table('pcare_tindakan_ralan_diberikan')->insert([
                'no_rawat' => Fixture::NO_RAWAT, 'noKunjungan' => '000000001126P000001',
                'kd_jenis_prw' => $code, 'tgl_perawatan' => '2026-11-02', 'jam' => '11:00:00',
            ]);
        }
        $response = $this->withHeader('X-Integration-Token', 'pdf-test-token')
            ->postJson(self::URL, ['no_rawat' => Fixture::NO_RAWAT])->assertOk();
        $this->assertGreaterThan(1, preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()));
        $this->assertCount(56, app(SppFktpService::class)->data(Fixture::NO_RAWAT)['services']);
    }
}
