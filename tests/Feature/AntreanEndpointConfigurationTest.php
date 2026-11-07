<?php

namespace Tests\Feature;

use App\Http\Controllers\BaseUrlConfigurationController;
use App\Models\BaseUrlConfiguration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AntreanEndpointConfigurationTest extends TestCase
{
    public function test_batal_url_can_be_saved_from_configuration_form_and_migration_preserves_existing_urls(): void
    {
        config(['database.connections.central' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('central');
        Schema::connection('central')->create('base_url_configurations', function (Blueprint $table) {
            $table->id();
            foreach (['base_url_pcare', 'base_url_antrean', 'base_url_antrean_panggil'] as $column) {
                $table->text($column);
            }
            $table->timestamps();
        });
        $configuration = BaseUrlConfiguration::create([
            'base_url_pcare' => 'https://example.test/pcare',
            'base_url_antrean' => 'https://example.test/antrean/add',
            'base_url_antrean_panggil' => 'https://example.test/antrean/panggil',
        ]);
        $migration = require database_path('migrations/2026_11_07_000001_add_antrean_batal_url_to_base_url_configurations.php');
        $migration->up();
        $this->assertNull($configuration->fresh()->base_url_antrean_batal);
        $request = Request::create('/konfigurasi-bridging/base-url/'.$configuration->id, 'PUT', [
            ...$configuration->only(['base_url_pcare', 'base_url_antrean', 'base_url_antrean_panggil']),
            'base_url_antrean_batal' => 'https://cancel.example.test/custom/antrean/batal/',
        ]);
        $request->setUserResolver(fn () => new class
        {
            public function isSuperadmin(): bool
            {
                return true;
            }
        });
        $request->setLaravelSession(app('session')->driver());
        (new BaseUrlConfigurationController)->update($request, $configuration);
        $configuration->refresh();
        $this->assertSame('https://cancel.example.test/custom/antrean/batal/', $configuration->base_url_antrean_batal);
        $this->assertSame('https://example.test/antrean/panggil', $configuration->base_url_antrean_panggil);
        $html = view('configurations.base-url.form', compact('configuration'))->render();
        $this->assertStringContainsString('name="base_url_antrean_batal"', $html);
        $this->assertStringContainsString('https://cancel.example.test/custom/antrean/batal/', $html);
        $migration->down();
        $this->assertFalse(Schema::connection('central')->hasColumn('base_url_configurations', 'base_url_antrean_batal'));
    }

    public function test_legacy_urls_are_migrated_and_custom_endpoints_are_saved_verbatim(): void
    {
        config(['database.connections.central' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('central');
        Schema::connection('central')->create('base_url_configurations', function (Blueprint $table) {
            $table->id();
            $table->text('base_url_pcare');
            $table->text('base_url_antrean');
            $table->timestamps();
        });
        $cases = [
            ['https://example.test/', 'https://example.test/antreanfktp'],
            ['https://example.test/antreanfktp/', 'https://example.test/antreanfktp'],
            ['https://example.test/antreanfktp_dev/antrean/add', 'https://example.test/antreanfktp_dev'],
            ['https://example.test/antreanfktp/antrean/panggil/', 'https://example.test/antreanfktp'],
        ];
        foreach ($cases as [$legacy]) {
            BaseUrlConfiguration::create(['base_url_pcare' => 'https://example.test/pcare', 'base_url_antrean' => $legacy]);
        }
        $migration = require database_path('migrations/2026_10_26_000001_split_antrean_endpoint_urls.php');
        $migration->up();
        foreach (BaseUrlConfiguration::orderBy('id')->get() as $index => $row) {
            $root = $cases[$index][1];
            $this->assertSame($root.'/antrean/add', $row->base_url_antrean);
            $this->assertSame($root.'/antrean/panggil', $row->base_url_antrean_panggil);
        }
        $row = BaseUrlConfiguration::firstOrFail();
        $row->update(['base_url_antrean' => 'https://add.example.test/custom/', 'base_url_antrean_panggil' => 'https://call.example.test/other/']);
        $row->refresh();
        $this->assertSame('https://add.example.test/custom/', $row->base_url_antrean);
        $this->assertSame('https://call.example.test/other/', $row->base_url_antrean_panggil);
        $migration->down();
        $this->assertFalse(Schema::connection('central')->hasColumn('base_url_configurations', 'base_url_antrean_panggil'));
    }
}
