<?php

namespace Tests\Feature;

use App\Http\Controllers\SatuSehatConfigurationController;
use App\Models\SatuSehatConfiguration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SatuSehatConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['clinic', 'central'] as $connection) {
            config(["database.connections.{$connection}" => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
            DB::purge($connection);
            Schema::connection($connection)->create('ss_config', function (Blueprint $table) {
                $table->id();
                foreach (['client_id', 'client_secret', 'organization_id', 'location_id'] as $field) {
                    $table->text($field);
                }
            });
        }
        DB::setDefaultConnection('central');
    }

    private function data(): array
    {
        return ['client_id' => 'client', 'client_secret' => 'secret-plain-existing', 'organization_id' => 'org', 'location_id' => 'loc'];
    }

    private function request(array $data = [], bool $admin = true): Request
    {
        $request = Request::create('/konfigurasi-bridging/satu-sehat', 'POST', $data);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => new class($admin)
        {
            public function __construct(private bool $admin) {}

            public function isSuperadmin(): bool
            {
                return $this->admin;
            }
        });

        return $request;
    }

    public function test_crud_uses_selected_clinic_database_and_preserves_blank_secret(): void
    {
        DB::connection('central')->table('ss_config')->insert(['id' => 1] + $this->data());
        $controller = new SatuSehatConfigurationController;
        $controller->store($this->request($this->data() + ['clinic_id' => 999]));
        $this->assertDatabaseHas('ss_config', ['id' => 1, 'client_secret' => 'secret-plain-existing'], 'clinic');
        $controller->update($this->request(['client_id' => 'changed', 'client_secret' => '', 'organization_id' => 'org2', 'location_id' => 'loc2']), '1');
        $this->assertDatabaseHas('ss_config', ['client_id' => 'changed', 'client_secret' => 'secret-plain-existing'], 'clinic');
        $this->assertDatabaseHas('ss_config', ['client_id' => 'client', 'organization_id' => 'org'], 'central');
        $this->assertArrayNotHasKey('client_secret', SatuSehatConfiguration::first()->toArray());
        $controller->destroy($this->request(), '1');
        $this->assertDatabaseCount('ss_config', 0, 'clinic');
        $this->assertDatabaseCount('ss_config', 1, 'central');
    }

    public function test_reads_legacy_plaintext_configuration_without_timestamps_or_clinic_id(): void
    {
        DB::connection('clinic')->table('ss_config')->insert(['id' => 7] + $this->data());
        $controller = new SatuSehatConfigurationController;
        $rows = $controller->index($this->request())->getData()['configurations'];
        $this->assertSame('secret-plain-existing', $rows->first()->client_secret);
        $controller->update($this->request($this->data() + ['id' => 8]), '7');
        $this->assertDatabaseHas('ss_config', ['id' => 7], 'clinic');
        $html = view('configurations.satu-sehat.form', ['configuration' => $rows->first()])->render();
        $this->assertStringContainsString('value="secret-plain-existing"', $html);
        $this->assertStringNotContainsString('name="clinic_id"', $html);
    }

    public function test_duplicate_configuration_and_invalid_input_do_not_flash_secret(): void
    {
        $controller = new SatuSehatConfigurationController;
        $controller->store($this->request($this->data()));
        foreach ([$this->data(), ['client_secret' => 'do-not-flash']] as $data) {
            try {
                $controller->store($this->request($data));
                $this->fail('Invalid or duplicate configuration was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('client_id', $exception->errors());
                $this->assertArrayNotHasKey('client_secret', session()->get('_old_input', []));
            }
        }
        $this->assertDatabaseCount('ss_config', 1, 'clinic');
    }

    public function test_missing_table_shows_notice_and_blocks_creation_in_view(): void
    {
        Schema::connection('clinic')->drop('ss_config');
        $view = (new SatuSehatConfigurationController)->index($this->request())->getData();
        $this->assertTrue($view['configurationUnavailable']);
        $this->assertStringContainsString('ss_config', $view['configurationNotice']);
    }

    public function test_non_superadmin_cannot_modify_configuration(): void
    {
        try {
            (new SatuSehatConfigurationController)->store($this->request($this->data(), false));
            $this->fail('Non-superadmin was allowed.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertDatabaseCount('ss_config', 0, 'clinic');
    }
}
