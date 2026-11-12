<?php

namespace Tests\Feature;

use App\Http\Controllers\KioskConfigurationController;
use App\Models\KioskConfiguration;
use App\Models\User;
use App\Services\MedicalRecordFormat;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class KioskConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['central', 'clinic'] as $connection) {
            config(["database.connections.{$connection}" => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
            DB::purge($connection);
        }
        (require database_path('migrations/2026_11_12_000002_create_kiosk_configurations_table.php'))->up();
        Schema::connection('clinic')->create('pasien', fn (Blueprint $table) => $table->string('no_rkm_medis', 15)->primary());
        Schema::connection('clinic')->create('set_no_rkm_medis', fn (Blueprint $table) => $table->string('no_rkm_medis', 15));
        DB::connection('clinic')->table('set_no_rkm_medis')->insert(['no_rkm_medis' => '000000']);
    }

    private function request(array $data, bool $admin = true): Request
    {
        $request = Request::create('/konfigurasi/anjungan', 'PUT', $data);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => new User(['role' => $admin ? 'superadmin' : 'admin']));

        return $request;
    }

    private function settings(array $overrides = []): array
    {
        return $overrides + ['rm_digits' => 6, 'rm_use_separator' => 1, 'rm_separator' => '-', 'rm_groups' => '2-2-2'];
    }

    public function test_default_format_matches_existing_kiosk_and_empty_database_starts_at_one(): void
    {
        $format = new MedicalRecordFormat(KioskConfiguration::current());
        $this->assertSame('00-00-01', $format->next());
        $this->assertSame(8, $format->clientSettings()['maxlength']);
        $this->assertSame(1, preg_match('~^'.$format->pattern().'$~D', '12-34-56'));
    }

    public function test_disabling_separator_continues_mixed_legacy_sequence_and_preserves_patients(): void
    {
        DB::connection('clinic')->table('pasien')->insert([
            ['no_rkm_medis' => '00-00-09'], ['no_rkm_medis' => '000012'],
            ['no_rkm_medis' => '00.00.14'], ['no_rkm_medis' => '00/00/15'], ['no_rkm_medis' => 'OLD-ABC'],
        ]);
        $controller = new KioskConfigurationController;
        DB::connection('clinic')->table('set_no_rkm_medis')->update(['no_rkm_medis' => '000015']);
        $controller->update($this->request($this->settings(['rm_use_separator' => 0])));
        $format = new MedicalRecordFormat(KioskConfiguration::current());
        $this->assertSame('000016', $format->next());
        $this->assertTrue($format->existsEquivalent('000009'));
        $this->assertFalse($format->existsEquivalent('000016'));
        $controller->update($this->request($this->settings()));
        $this->assertSame('00-00-16', (new MedicalRecordFormat(KioskConfiguration::current()))->next());
        $this->assertDatabaseHas('pasien', ['no_rkm_medis' => '00-00-09'], 'clinic');
        $this->assertDatabaseCount('kiosk_configurations', 1, 'central');
    }

    public function test_custom_grouping_and_separator_patterns_match_both_backend_and_client_example(): void
    {
        foreach (['-' => '000-001', '.' => '000.001', '/' => '000/001'] as $separator => $expected) {
            (new KioskConfigurationController)->update($this->request($this->settings(['rm_groups' => '3-3', 'rm_separator' => $separator])));
            $format = new MedicalRecordFormat(KioskConfiguration::current());
            $this->assertSame($expected, $format->format(1));
            $this->assertSame(1, preg_match('~^'.$format->pattern().'$~D', $expected));
            $this->assertSame(0, preg_match('~^'.$format->pattern().'$~D', '000001'));
        }
    }

    public function test_eight_digit_sequence_uses_simrs_counter_without_a_fixed_prefix(): void
    {
        (new KioskConfigurationController)->update($this->request($this->settings(['rm_digits' => 8, 'rm_use_separator' => 0])));
        $format = new MedicalRecordFormat(KioskConfiguration::current());
        DB::connection('clinic')->table('pasien')->insert([
            ['no_rkm_medis' => '00025971'], ['no_rkm_medis' => '00025972'], ['no_rkm_medis' => '00025973'],
        ]);
        DB::connection('clinic')->table('set_no_rkm_medis')->update(['no_rkm_medis' => '00025973']);
        $this->assertSame('00025974', $format->next());
        DB::connection('clinic')->table('pasien')->insert(['no_rkm_medis' => '00099999']);
        DB::connection('clinic')->table('set_no_rkm_medis')->update(['no_rkm_medis' => '00099999']);
        $this->assertSame('00100000', $format->next());
        DB::connection('clinic')->table('pasien')->insert(['no_rkm_medis' => '01712214']);
        // Nomor besar dari urutan lain tidak mengalahkan pencatat SIMRS.
        $this->assertSame('00100000', $format->next());
        DB::connection('clinic')->table('set_no_rkm_medis')->update(['no_rkm_medis' => '01712214']);
        $this->assertSame('01712215', $format->next());
        // Baris yang dimasukkan belakangan tidak mengalahkan nilai numerik terbesar.
        DB::connection('clinic')->table('pasien')->insert(['no_rkm_medis' => '00025975']);
        $this->assertSame('01712215', $format->next());
        $this->assertSame('01712214', DB::connection('clinic')->table('set_no_rkm_medis')->value('no_rkm_medis'));
    }

    public function test_invalid_grouping_or_excessive_length_cannot_be_saved(): void
    {
        foreach ([$this->settings(['rm_groups' => '2-2']), $this->settings(['rm_digits' => 12, 'rm_groups' => '2-2-2-2-2-2']),
            $this->settings(['rm_separator' => '"']), $this->settings(['rm_digits' => 16])] as $data) {
            try {
                (new KioskConfigurationController)->update($this->request($data));
                $this->fail('Invalid RM configuration was saved.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
        $this->assertDatabaseCount('kiosk_configurations', 0, 'central');
    }

    public function test_stale_counter_skips_used_numbers_without_advancing_until_save(): void
    {
        (new KioskConfigurationController)->update($this->request($this->settings(['rm_digits' => 8, 'rm_use_separator' => 0])));
        DB::connection('clinic')->table('set_no_rkm_medis')->update(['no_rkm_medis' => '00025970']);
        DB::connection('clinic')->table('pasien')->insert([
            ['no_rkm_medis' => '00025971'], ['no_rkm_medis' => '00025972'], ['no_rkm_medis' => '00025973'],
            ['no_rkm_medis' => '01712214'],
        ]);
        $format = new MedicalRecordFormat(KioskConfiguration::current());
        $this->assertSame('00025974', $format->next());
        $this->assertSame('00025970', DB::connection('clinic')->table('set_no_rkm_medis')->value('no_rkm_medis'));
        DB::connection('clinic')->transaction(function () use ($format) {
            $previous = $format->lockCounterForSave('00025974');
            DB::connection('clinic')->table('pasien')->insert(['no_rkm_medis' => '00025974']);
            $format->advanceAfterSave('00025974', $previous);
        });
        $this->assertSame('00025974', DB::connection('clinic')->table('set_no_rkm_medis')->value('no_rkm_medis'));
        $this->assertSame('00025975', $format->next());
    }

    public function test_counter_and_patient_both_roll_back_when_saving_fails(): void
    {
        $format = new MedicalRecordFormat(KioskConfiguration::current());
        try {
            DB::connection('clinic')->transaction(function () use ($format) {
                $previous = $format->lockCounterForSave('00-00-01');
                DB::connection('clinic')->table('pasien')->insert(['no_rkm_medis' => '00-00-01']);
                $format->advanceAfterSave('00-00-01', $previous);
                throw new \RuntimeException('Simulated failure after insert.');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated failure after insert.', $exception->getMessage());
        }
        $this->assertDatabaseCount('pasien', 0, 'clinic');
        $this->assertSame('000000', DB::connection('clinic')->table('set_no_rkm_medis')->value('no_rkm_medis'));
    }

    public function test_saved_counter_has_no_separator_and_stale_suggestion_is_rejected(): void
    {
        $format = new MedicalRecordFormat(KioskConfiguration::current());
        DB::connection('clinic')->transaction(function () use ($format) {
            $previous = $format->lockCounterForSave('00-00-01');
            DB::connection('clinic')->table('pasien')->insert(['no_rkm_medis' => '00-00-01']);
            $format->advanceAfterSave('00-00-01', $previous);
        });
        $this->assertSame('000001', DB::connection('clinic')->table('set_no_rkm_medis')->value('no_rkm_medis'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('terakhir sudah berubah');
        DB::connection('clinic')->transaction(fn () => $format->lockCounterForSave('00-00-01'));
    }

    public function test_sequence_overflow_is_rejected_instead_of_truncating_digits(): void
    {
        DB::connection('clinic')->table('pasien')->insert(['no_rkm_medis' => '99-99-99']);
        DB::connection('clinic')->table('set_no_rkm_medis')->update(['no_rkm_medis' => '999999']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('melebihi kapasitas');
        (new MedicalRecordFormat(KioskConfiguration::current()))->next();
    }

    public function test_patient_endpoint_validation_follows_the_saved_format(): void
    {
        (new KioskConfigurationController)->update($this->request($this->settings(['rm_use_separator' => 0])));
        $response = $this->postJson('/anjungan/pasien', ['no_rkm_medis' => '00-00-01']);
        $this->assertSame(422, $response->status(), substr($response->getContent(), 0, 2500));
        $response->assertJsonValidationErrors('no_rkm_medis');
        $this->postJson('/anjungan/pasien', ['no_rkm_medis' => '000001'])
            ->assertUnprocessable()->assertJsonValidationErrors('participant_token')
            ->assertJsonMissingValidationErrors('no_rkm_medis');
        (new KioskConfigurationController)->update($this->request($this->settings()));
        $this->postJson('/anjungan/pasien', ['no_rkm_medis' => '00-00-01'])
            ->assertUnprocessable()->assertJsonMissingValidationErrors('no_rkm_medis');
    }

    public function test_settings_render_with_preview_and_only_superadmin_can_update(): void
    {
        $user = new User(['name' => 'Admin', 'email' => 'admin@example.test', 'role' => 'superadmin']);
        $this->actingAs($user);
        view()->share('activeClinic', null);
        view()->share('errors', new ViewErrorBag);
        $html = (new KioskConfigurationController)->index($this->request([]))->render();
        $this->assertStringContainsString('00-00-01', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="rm_use_separator"', $html);
        try {
            (new KioskConfigurationController)->update($this->request($this->settings(), false));
            $this->fail('Non-superadmin changed kiosk settings.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
