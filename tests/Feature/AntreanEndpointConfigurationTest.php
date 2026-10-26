<?php

namespace Tests\Feature;

use App\Models\BaseUrlConfiguration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AntreanEndpointConfigurationTest extends TestCase
{
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
