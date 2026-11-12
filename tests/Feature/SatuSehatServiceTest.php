<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Services\ClinicContext;
use App\Models\SatuSehatConfiguration;
use App\Models\ServiceRun;
use App\Services\LocalClinic;
use App\Services\SatuSehatService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SatuSehatServiceTest extends TestCase
{
    private ClinicContext $installation;

    private ServiceRun $run;

    private SatuSehatService $service;

    private const NO = '2026/11/11/000001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        foreach (['central', 'clinic'] as $connection) {
            config(["database.connections.{$connection}" => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
            DB::purge($connection);
        }
        Schema::connection('central')->create('satu_sehat_base_url_configurations', function (Blueprint $table) {
            $table->id();
            $table->text('auth_url');
            $table->text('fhir_url');
        });
        DB::connection('central')->table('satu_sehat_base_url_configurations')->insert([
            'auth_url' => 'https://ss.example.test/oauth', 'fhir_url' => 'https://ss.example.test/fhir/',
        ]);
        Schema::connection('central')->create('service_runs', function (Blueprint $table) {
            $table->id();
            $table->integer('clinic_id')->nullable();
            $table->string('service_name');
            $table->string('status');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('last_heartbeat_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->text('message')->nullable();
            foreach (['processed', 'succeeded', 'failed'] as $name) {
                $table->integer($name)->default(0);
            }
            $table->timestamps();
        });
        Schema::connection('central')->create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('clinic_id')->nullable();
            $table->integer('service_run_id');
            foreach (['service_name', 'level', 'status', 'reference', 'message'] as $name) {
                $table->text($name);
            }
            $table->integer('response_code')->nullable();
            $table->timestamps();
        });
        Schema::connection('clinic')->create('satu_sehat_service_resources', function (Blueprint $table) {
            $table->string('resource_key')->primary();
            $table->string('no_rawat');
            $table->string('resource_type');
            $table->string('resource_id')->nullable();
            $table->string('status');
            $table->integer('http_code')->nullable();
            $table->integer('attempts')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->timestamps();
        });
        $tables = [
            'reg_periksa' => ['no_rawat', 'tgl_registrasi', 'jam_reg', 'status_lanjut', 'stts', 'no_rkm_medis', 'kd_dokter', 'kd_poli'],
            'pasien' => ['no_rkm_medis', 'nm_pasien', 'no_ktp'], 'dokter' => ['kd_dokter', 'nm_dokter'],
            'pegawai' => ['nik', 'no_ktp'], 'poliklinik' => ['kd_poli', 'nm_poli'],
            'diagnosa_pasien' => ['no_rawat', 'kd_penyakit', 'status'], 'penyakit' => ['kd_penyakit', 'nm_penyakit'],
            'pemeriksaan_ralan' => ['no_rawat', 'tgl_perawatan', 'jam_rawat', 'nip', 'penilaian', 'rtl', 'suhu_tubuh', 'respirasi', 'nadi', 'spo2', 'gcs', 'tinggi', 'berat', 'lingkar_perut', 'tensi', 'kesadaran'],
            'prosedur_pasien' => ['no_rawat', 'kode', 'status'], 'icd9' => ['kode', 'deskripsi_panjang'],
            'resep_obat' => ['no_rawat', 'no_resep', 'kd_dokter', 'tgl_peresepan', 'jam_peresepan', 'tgl_penyerahan', 'jam_penyerahan', 'tgl_perawatan', 'jam'],
            'resep_dokter' => ['no_resep', 'kode_brng', 'jml', 'aturan_pakai'],
            'satu_sehat_mapping_obat' => ['kode_brng', 'obat_display', 'route_system', 'route_code', 'route_display', 'denominator_code', 'denominator_system'],
            'satu_sehat_medication' => ['kode_brng', 'id_medication'],
            'detail_pemberian_obat' => ['no_rawat', 'tgl_perawatan', 'jam', 'kode_brng', 'no_batch', 'no_faktur', 'kd_bangsal', 'jml'],
            'aturan_pakai' => ['no_rawat', 'tgl_perawatan', 'jam', 'kode_brng', 'aturan'],
            'satu_sehat_mapping_lokasi_depo_farmasi' => ['kd_bangsal', 'id_lokasi_satusehat'],
            'satu_sehat_encounter_new' => ['no_rawat', 'id_encounter', 'patient_id', 'status', 'response'],
        ];
        foreach ($tables as $name => $columns) {
            Schema::connection('clinic')->create($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $name) {
                    $table->string($name)->nullable();
                }
            });
        }
        $data = [
            'reg_periksa' => ['no_rawat' => self::NO, 'tgl_registrasi' => '2026-11-11', 'jam_reg' => '08:00:00', 'status_lanjut' => 'Ralan', 'stts' => 'Sudah', 'no_rkm_medis' => '001', 'kd_dokter' => 'D1', 'kd_poli' => 'P1'],
            'pasien' => ['no_rkm_medis' => '001', 'nm_pasien' => 'Test Patient', 'no_ktp' => '1111111111111111'],
            'dokter' => ['kd_dokter' => 'D1', 'nm_dokter' => 'Test Doctor'], 'pegawai' => ['nik' => 'D1', 'no_ktp' => '2222222222222222'],
            'poliklinik' => ['kd_poli' => 'P1', 'nm_poli' => 'Umum'],
            'diagnosa_pasien' => ['no_rawat' => self::NO, 'kd_penyakit' => 'A01', 'status' => 'Ralan'], 'penyakit' => ['kd_penyakit' => 'A01', 'nm_penyakit' => 'Test'],
            'pemeriksaan_ralan' => ['no_rawat' => self::NO, 'tgl_perawatan' => '2026-11-11', 'jam_rawat' => '08:30:00', 'nip' => 'D1', 'penilaian' => 'Assessment', 'rtl' => 'Plan', 'suhu_tubuh' => '36,5', 'respirasi' => '20', 'nadi' => '80', 'spo2' => '98', 'gcs' => '15', 'tinggi' => '170', 'berat' => '60', 'lingkar_perut' => '80', 'tensi' => '120/80', 'kesadaran' => 'Compos Mentis'],
            'prosedur_pasien' => ['no_rawat' => self::NO, 'kode' => '89.0', 'status' => 'Ralan'], 'icd9' => ['kode' => '89.0', 'deskripsi_panjang' => 'Test procedure'],
            'resep_obat' => ['no_rawat' => self::NO, 'no_resep' => 'R1', 'kd_dokter' => 'D1', 'tgl_peresepan' => '2026-11-11', 'jam_peresepan' => '09:00:00', 'tgl_penyerahan' => '2026-11-11', 'jam_penyerahan' => '09:30:00', 'tgl_perawatan' => '2026-11-11', 'jam' => '09:30:00'],
            'resep_dokter' => ['no_resep' => 'R1', 'kode_brng' => 'B1', 'jml' => '10', 'aturan_pakai' => '3 x 1'],
            'satu_sehat_mapping_obat' => ['kode_brng' => 'B1', 'obat_display' => 'Test medication', 'route_system' => 'http://www.whocc.no/atc', 'route_code' => 'O', 'route_display' => 'Oral', 'denominator_code' => 'TAB', 'denominator_system' => 'http://terminology.kemkes.go.id'],
            'satu_sehat_medication' => ['kode_brng' => 'B1', 'id_medication' => 'med-1'],
            'detail_pemberian_obat' => ['no_rawat' => self::NO, 'tgl_perawatan' => '2026-11-11', 'jam' => '09:30:00', 'kode_brng' => 'B1', 'no_batch' => 'batch1', 'no_faktur' => 'f1', 'kd_bangsal' => 'DEP1', 'jml' => '10'],
            'aturan_pakai' => ['no_rawat' => self::NO, 'tgl_perawatan' => '2026-11-11', 'jam' => '09:30:00', 'kode_brng' => 'B1', 'aturan' => '3 x 1'],
            'satu_sehat_mapping_lokasi_depo_farmasi' => ['kd_bangsal' => 'DEP1', 'id_lokasi_satusehat' => 'location-pharmacy'],
        ];
        foreach ($data as $table => $values) {
            DB::connection('clinic')->table($table)->insert($values);
        }
        Schema::connection('clinic')->create('ss_config', function (Blueprint $table) {
            $table->id();
            foreach (['client_id', 'client_secret', 'organization_id', 'location_id'] as $field) {
                $table->text($field);
            }
        });
        SatuSehatConfiguration::create(['id' => 1, 'client_id' => 'test-client', 'client_secret' => 'test-secret', 'organization_id' => 'org-1', 'location_id' => 'loc-1']);
        $clinic = new Clinic(['name' => 'Test clinic']);
        $this->installation = new ClinicContext($clinic);

        $this->run = ServiceRun::create(['service_name' => 'satu_sehat', 'status' => 'running']);
        $this->service = app(SatuSehatService::class);
        $this->service->configure($this->installation);
        Http::preventStrayRequests();
    }

    private function fake(?callable $override = null): void
    {
        Http::fake(function ($request) use ($override) {
            if ($override && ($result = $override($request)) !== null) {
                return $result;
            }
            if (parse_url($request->url(), PHP_URL_PATH) === '/oauth') {
                return Http::response(['access_token' => 'test-token', 'expires_in' => 3600]);
            }
            if ($request->method() === 'GET') {
                $type = basename(parse_url($request->url(), PHP_URL_PATH));

                return Http::response(['resourceType' => 'Bundle', 'entry' => $type === 'Encounter' ? [] : [['resource' => ['id' => strtolower($type).'-1']]]]);
            }

            return Http::response(['resourceType' => ($request->data()['resourceType'] ?? null), 'id' => 'id-'.substr(hash('sha256', json_encode($request->data())), 0, 16)], 201);
        });
    }

    private function scan(): void
    {
        $this->service->scan($this->installation, $this->run, '2026-11-11', '2026-11-11', fn () => null);
    }

    public function test_all_resources_are_sent_once_with_correct_references_and_dosage(): void
    {
        $this->fake();
        $this->scan();
        $this->assertDatabaseCount('satu_sehat_service_resources', 18, 'clinic');
        $this->assertSame(18, DB::connection('clinic')->table('satu_sehat_service_resources')->where('status', 'success')->count());
        $this->assertSame(0, (int) $this->run->fresh()->failed);
        $this->scan();
        $posts = Http::recorded(fn ($request) => $request->method() === 'POST' && str_contains($request->url(), '/fhir/'));
        $this->assertCount(18, $posts);
        $this->assertCount(1, Http::recorded(fn ($request) => parse_url($request->url(), PHP_URL_PATH) === '/oauth'));
        Http::assertSent(fn ($request) => $request->url() === 'https://ss.example.test/oauth?grant_type=client_credentials'
            && $request['client_id'] === 'test-client' && $request['client_secret'] === 'test-secret');
        Http::assertSent(fn ($request) => ($request->data()['resourceType'] ?? null) === 'Encounter' && $request['period']['start'] === '2026-11-11T01:00:00+00:00');
        Http::assertSent(fn ($request) => ($request->data()['resourceType'] ?? null) === 'MedicationRequest'
            && $request['dosageInstruction'][0]['doseAndRate'][0]['doseQuantity']['value'] === 1.0
            && $request['dispenseRequest']['quantity']['value'] === 10.0);
        $this->assertStringNotContainsString('test-secret', DB::connection('central')->table('service_logs')->pluck('message')->implode(' '));
    }

    public function test_ambiguous_result_is_not_resent(): void
    {
        $this->fake(fn ($request) => $request->method() === 'POST' && ($request->data()['resourceType'] ?? null) === 'Encounter' ? Http::response('gateway error', 502) : null);
        $this->scan();
        $this->travel(10)->minutes();
        $this->scan();
        $this->assertDatabaseHas('satu_sehat_service_resources', ['resource_type' => 'Encounter', 'status' => 'uncertain', 'attempts' => 1], 'clinic');
        $this->assertCount(1, Http::recorded(fn ($request) => ($request->data()['resourceType'] ?? null) === 'Encounter'));
    }

    public function test_rejection_retries_after_cooldown_and_cancellation_is_excluded(): void
    {
        $this->fake(fn ($request) => ($request->data()['resourceType'] ?? null) === 'Encounter' ? Http::response(['resourceType' => 'OperationOutcome'], 400) : null);
        $this->scan();
        $this->scan();
        $this->assertDatabaseHas('satu_sehat_service_resources', ['status' => 'failed', 'attempts' => 1], 'clinic');
        $this->travel(6)->minutes();
        $this->scan();
        $this->assertDatabaseHas('satu_sehat_service_resources', ['status' => 'failed', 'attempts' => 2], 'clinic');
        DB::connection('clinic')->table('reg_periksa')->update(['stts' => 'Batal']);
        $this->travel(6)->minutes();
        $this->scan();
        $this->assertDatabaseHas('satu_sehat_service_resources', ['attempts' => 2], 'clinic');
    }

    public function test_existing_legacy_encounter_is_reused(): void
    {
        DB::connection('clinic')->table('satu_sehat_encounter_new')->insert(['no_rawat' => self::NO, 'status' => 'success', 'id_encounter' => 'old-encounter', 'patient_id' => 'patient-1']);
        $this->fake();
        $this->scan();
        Http::assertNotSent(fn ($request) => ($request->data()['resourceType'] ?? null) === 'Encounter');
        $this->assertDatabaseHas('satu_sehat_service_resources', ['resource_type' => 'Encounter', 'resource_id' => 'old-encounter', 'status' => 'success'], 'clinic');
        Http::assertSent(fn ($request) => ($request->data()['resourceType'] ?? null) === 'Condition' && $request['encounter']['reference'] === 'Encounter/old-encounter');
    }

    public function test_missing_diagnosis_does_not_block_other_resources(): void
    {
        DB::connection('clinic')->table('diagnosa_pasien')->delete();
        $this->fake();
        $this->scan();
        $this->assertDatabaseHas('satu_sehat_service_resources', ['resource_type' => 'Observation', 'status' => 'success'], 'clinic');
        $this->assertDatabaseHas('satu_sehat_service_resources', ['resource_type' => 'MedicationRequest', 'status' => 'success'], 'clinic');
    }

    public function test_timeout_and_malformed_success_require_reconciliation(): void
    {
        $this->fake(fn ($request) => ($request->data()['resourceType'] ?? null) === 'Encounter' ? Http::failedConnection() : null);
        $this->scan();
        $this->scan();
        $this->assertDatabaseHas('satu_sehat_service_resources', ['status' => 'uncertain', 'attempts' => 1], 'clinic');
        DB::connection('clinic')->table('satu_sehat_service_resources')->delete();
        $this->fake(fn ($request) => ($request->data()['resourceType'] ?? null) === 'Encounter' ? Http::response('not json', 201) : null);
        $this->scan();
        $this->assertDatabaseHas('satu_sehat_service_resources', ['status' => 'uncertain', 'resource_id' => null], 'clinic');
    }

    public function test_check_command_uses_no_bpjs_configuration_and_sends_nothing(): void
    {

        $this->mock(LocalClinic::class, function ($mock) {
            $mock->shouldReceive('resolveForPdf')->once()->andReturn($this->installation);
            $mock->shouldReceive('configureClinicDatabase')->once()->with($this->installation);
            $mock->shouldNotReceive('resolve');
        });
        Http::fake();
        $this->artisan('service:satu-sehat', ['--check' => true])
            ->expectsOutput('Konfigurasi dan tabel kontrol tersedia. Tidak ada data yang dikirim.')
            ->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_inpatient_uses_inpatient_examination_and_categories(): void
    {
        $exam = (array) DB::connection('clinic')->table('pemeriksaan_ralan')->first();
        Schema::connection('clinic')->create('pemeriksaan_ranap', function (Blueprint $table) use ($exam) {
            foreach (array_keys($exam) as $column) {
                $table->string($column)->nullable();
            }
        });
        $exam['jam_rawat'] = '08:45:00';
        DB::connection('clinic')->table('pemeriksaan_ranap')->insert($exam);
        DB::connection('clinic')->table('reg_periksa')->update(['status_lanjut' => 'Ranap']);
        foreach (['diagnosa_pasien', 'prosedur_pasien'] as $table) {
            DB::connection('clinic')->table($table)->update(['status' => 'Ranap']);
        }
        $this->fake();
        $this->scan();
        Http::assertSent(fn ($request) => ($request->data()['resourceType'] ?? null) === 'Encounter' && $request['class']['code'] === 'IMP');
        Http::assertSent(fn ($request) => ($request->data()['resourceType'] ?? null) === 'Observation' && $request['effectiveDateTime'] === '2026-11-11T01:45:00+00:00');
        Http::assertSent(fn ($request) => ($request->data()['resourceType'] ?? null) === 'MedicationRequest' && $request['category'][0]['coding'][0]['code'] === 'inpatient');
        $this->assertSame(0, (int) $this->run->fresh()->failed);
    }

    public function test_default_scan_includes_yesterday_across_month_boundary_in_jakarta(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-01 00:15:00', 'Asia/Jakarta'));

        $this->mock(LocalClinic::class, function ($mock) {
            $mock->shouldReceive('resolveForPdf')->once()->andReturn($this->installation);
            $mock->shouldReceive('configureClinicDatabase')->once();
        });
        $this->mock(SatuSehatService::class, function ($mock) {
            $mock->shouldReceive('configure')->once()->with($this->installation);
            $mock->shouldReceive('scan')->once()->withArgs(function ($installation, $run, $from, $to, $output) {
                $this->assertSame('2026-10-31', $from);
                $this->assertSame('2026-11-01', $to);

                return true;
            });
        });
        $this->artisan('service:satu-sehat', ['--once' => true])->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_terminal_names_observations_and_all_items_follow_resource_order(): void
    {
        foreach (['resep_dokter', 'satu_sehat_mapping_obat', 'satu_sehat_medication', 'detail_pemberian_obat', 'aturan_pakai'] as $table) {
            $item = (array) DB::connection('clinic')->table($table)->first();
            $item['kode_brng'] = 'B2';
            DB::connection('clinic')->table($table)->insert($item);
        }
        $messages = [];
        $output = function ($level, $message) use (&$messages) {
            $messages[] = $message;
        };
        $this->fake();
        $this->service->scan($this->installation, $this->run, '2026-11-11', '2026-11-11', $output);
        $text = implode("\n", $messages);
        $this->assertStringContainsString('Observation Tinggi Badan | BERHASIL TERKIRIM', $text);
        $this->assertStringContainsString('Nama     : Test Patient', $text);
        $this->assertSame(1, substr_count($text, self::NO));
        $this->assertStringNotContainsString('03 Observation', $text);
        $this->assertStringContainsString(str_repeat('-', 72), $text);
        $this->assertStringContainsString('Observation Suhu | DATA TERKIRIM: 36.5 Cel', $text);
        $ranks = ['Encounter' => 1, 'Condition' => 2, 'Observation' => 3, 'Procedure' => 4,
            'ClinicalImpression' => 5, 'MedicationRequest' => 6, 'MedicationDispense' => 7, 'MedicationStatement' => 8, 'CarePlan' => 9];
        $order = Http::recorded(fn ($request) => str_contains($request->url(), '/fhir/') && $request->method() === 'POST')
            ->map(fn ($pair) => $ranks[$pair[0]['resourceType']])->values()->all();
        $sorted = $order;
        sort($sorted);
        $this->assertSame($sorted, $order);
        $this->assertSame(2, count(array_filter($order, fn ($rank) => $rank === 6)));
        $messages = [];
        $this->service->scan($this->installation, $this->run, '2026-11-11', '2026-11-11', $output);
        $text = implode("\n", $messages);
        $this->assertStringContainsString('Observation Suhu | SUDAH TERKIRIM | ID:', $text);
        $this->assertStringNotContainsString('| MENGIRIM', $text);
    }
}
