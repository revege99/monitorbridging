<?php

namespace Tests\Feature\Api;

use App\Models\BpjsConfiguration;
use App\Models\Clinic;
use App\Models\InstallationSetting;
use App\Services\ActiveInstallation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AntreanFktpCancelTest extends TestCase
{
    private const URL = '/api/integration/antrean-fktp/batal';

    private const TARGET = 'https://bpjs.example.test/custom/antrean/batal/';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.khanza.integration_token' => 'test-token']);
        foreach (['central', 'clinic'] as $connection) {
            config(["database.connections.$connection" => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
            DB::purge($connection);
        }
        foreach ([
            'reg_periksa' => ['no_rawat', 'no_rkm_medis', 'tgl_registrasi', 'kd_poli'],
            'pasien' => ['no_rkm_medis', 'no_peserta'],
            'maping_poliklinik_pcare' => ['kd_poli_rs', 'kd_poli_pcare'],
        ] as $name => $columns) {
            Schema::connection('clinic')->create($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->string($column);
                }
            });
        }
        Schema::connection('central')->create('base_url_configurations', function (Blueprint $table) {
            $table->id();
            $table->text('base_url_antrean_batal')->nullable();
        });
        Schema::connection('central')->create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('clinic_id');
            foreach (['service_name', 'level', 'status', 'reference', 'message'] as $column) {
                $table->text($column);
            }
            $table->integer('response_code')->nullable();
            $table->integer('duration_ms');
            $table->timestamps();
        });
        DB::connection('central')->table('base_url_configurations')->insert(['base_url_antrean_batal' => self::TARGET]);
        DB::connection('clinic')->table('reg_periksa')->insert([
            'no_rawat' => '2026/11/07/000001', 'no_rkm_medis' => '000001', 'tgl_registrasi' => '2026-11-07', 'kd_poli' => 'POLI1',
        ]);
        DB::connection('clinic')->table('pasien')->insert(['no_rkm_medis' => '000001', 'no_peserta' => '0000045258563']);
        DB::connection('clinic')->table('maping_poliklinik_pcare')->insert(['kd_poli_rs' => 'POLI1', 'kd_poli_pcare' => '001']);

        $bpjs = new BpjsConfiguration;
        $bpjs->cons_id = 'test-cons';
        $bpjs->secret_key = 'test-secret';
        $bpjs->user_key_antrol = 'antrol-key';
        $bpjs->user_key_pcare = 'pcare-key';
        $clinic = new Clinic;
        $clinic->setRelation('bpjsConfiguration', $bpjs);
        $installation = new InstallationSetting(['clinic_id' => 1]);
        $installation->setRelation('clinic', $clinic);
        $this->mock(ActiveInstallation::class, function ($mock) use ($installation) {
            $mock->shouldReceive('resolve')->andReturn($installation);
            $mock->shouldReceive('configureClinicDatabase')->with($installation)->andReturnNull();
        });
        Http::preventStrayRequests();
        Http::fake([]);
    }

    public function test_authentication_and_input_are_checked_before_contacting_bpjs(): void
    {
        $this->postJson(self::URL)->assertUnauthorized();
        $this->postJson(self::URL.'?token=test-token')->assertUnauthorized();
        $this->withHeader('X-Integration-Token', 'test-token');
        foreach ([[], ['no_rawat' => ['bad'], 'alasan' => 'Alasan'], ['no_rawat' => '2026/11/07/000001', 'alasan' => '   ']] as $body) {
            $this->postJson(self::URL, $body)->assertStatus(400);
        }
        Http::assertNothingSent();
    }

    public function test_sends_matching_patient_data_and_antrol_headers_and_logs_result(): void
    {
        Http::fake([self::TARGET => Http::response(['metadata' => ['code' => 200, 'message' => 'Ok']])]);
        $response = $this->withHeader('X-Integration-Token', 'test-token')->postJson(self::URL, [
            'no_rawat' => '2026/11/07/000001', 'alasan' => 'Terjadi perubahan jadwal dokter',
        ])->assertOk()->assertJsonPath('metadata.code', 200);
        Http::assertSent(function ($request) {
            $timestamp = $request->header('X-timestamp')[0];

            return $request->url() === self::TARGET && $request->method() === 'POST'
                && $request->hasHeader('X-cons-id', 'test-cons')
                && $request->hasHeader('user_key', 'antrol-key')
                && $request->hasHeader('X-signature', base64_encode(hash_hmac('sha256', 'test-cons&'.$timestamp, 'test-secret', true)))
                && $request->hasHeader('Content-Type', 'application/json')
                && $request->data() === [
                    'tanggalperiksa' => '2026-11-07', 'kodepoli' => '001',
                    'nomorkartu' => '0000045258563', 'alasan' => 'Terjadi perubahan jadwal dokter',
                ];
        });
        Http::assertSentCount(1);
        $this->assertStringNotContainsString('antrol-key', $response->getContent());
        $log = DB::connection('central')->table('service_logs')->first();
        $this->assertSame('antrean_fktp_batal', $log->service_name);
        $this->assertSame('success', $log->status);
    }

    public function test_unknown_care_number_does_not_send_cancellation(): void
    {
        $this->withHeader('X-Integration-Token', 'test-token')->postJson(self::URL, [
            'no_rawat' => '2026/11/07/999999', 'alasan' => 'Perubahan jadwal',
        ])->assertOk()->assertJsonPath('metadata.code', 404);
        Http::assertNothingSent();
    }

    public function test_bpjs_rejection_is_returned_and_logged(): void
    {
        Http::fake([self::TARGET => Http::response(['metadata' => ['code' => 201, 'message' => 'Antrean tidak ditemukan']])]);
        $this->withHeader('X-Integration-Token', 'test-token')->postJson(self::URL, [
            'no_rawat' => '2026/11/07/000001', 'alasan' => 'Perubahan jadwal',
        ])->assertOk()->assertJsonPath('metadata.code', 201);
        $this->assertSame('failed', DB::connection('central')->table('service_logs')->value('status'));
    }

    public function test_connection_failure_is_logged_without_automatic_retry(): void
    {
        Http::fake([self::TARGET => Http::failedConnection()]);
        $this->withHeader('X-Integration-Token', 'test-token')->postJson(self::URL, [
            'no_rawat' => '2026/11/07/000001', 'alasan' => 'Perubahan jadwal',
        ])->assertOk()->assertJsonPath('metadata.code', 500);
        $this->assertSame('failed', DB::connection('central')->table('service_logs')->value('status'));
    }

    public function test_unconfigured_cancel_url_prevents_outgoing_request(): void
    {
        DB::connection('central')->table('base_url_configurations')->update(['base_url_antrean_batal' => null]);
        $this->withHeader('X-Integration-Token', 'test-token')->postJson(self::URL, [
            'no_rawat' => '2026/11/07/000001', 'alasan' => 'Perubahan jadwal',
        ])->assertStatus(500)->assertJsonPath('metadata.code', 500);
        Http::assertNothingSent();
    }

    public function test_non_json_response_is_treated_as_failure(): void
    {
        Http::fake([self::TARGET => Http::response('<html>Error</html>', 200)]);
        $this->withHeader('X-Integration-Token', 'test-token')->postJson(self::URL, [
            'no_rawat' => '2026/11/07/000001', 'alasan' => 'Perubahan jadwal',
        ])->assertOk()->assertJsonPath('metadata.code', 502);
        $this->assertSame('failed', DB::connection('central')->table('service_logs')->value('status'));
    }
}
