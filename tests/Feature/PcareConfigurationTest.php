<?php

namespace Tests\Feature;

use App\Http\Controllers\PcareConfigurationController;
use App\Models\PcareConfiguration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PcareConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.clinic' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('clinic');
        Schema::connection('clinic')->create('config_pcare', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            foreach (['SECRETKEYAPIPCARE', 'CONSIDAPIPCARE', 'USERKEYAPIPCARE', 'PASSPCARE', 'USERPCARE'] as $field) $table->text($field);
        });
    }

    private function requestData(array $data, bool $admin = true): Request
    {
        $request = Request::create('/konfigurasi/pcare', 'POST', $data);
        $request->setUserResolver(fn () => new class($admin) {
            public function __construct(private bool $admin) {}
            public function isSuperadmin(): bool { return $this->admin; }
        });
        $request->setLaravelSession(app('session')->driver());
        return $request;
    }

    private function credentials(): array
    {
        return ['id' => 1, 'CONSIDAPIPCARE' => 'consumer', 'USERPCARE' => 'user', 'SECRETKEYAPIPCARE' => 'secret', 'USERKEYAPIPCARE' => 'key', 'PASSPCARE' => 'pass'];
    }

    public function test_crud_uses_clinic_connection_even_when_default_is_central(): void
    {
        config(['database.connections.central' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('central');
        DB::setDefaultConnection('central');
        Schema::connection('central')->create('config_pcare', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            foreach (['SECRETKEYAPIPCARE', 'CONSIDAPIPCARE', 'USERKEYAPIPCARE', 'PASSPCARE', 'USERPCARE'] as $field) $table->text($field);
        });
        DB::connection('central')->table('config_pcare')->insert($this->credentials());

        $controller = new PcareConfigurationController;
        // The same ID in central must not affect clinic uniqueness validation.
        $controller->store($this->requestData($this->credentials()));
        $this->assertSame(1, PcareConfiguration::count());
        $controller->update($this->requestData(['CONSIDAPIPCARE' => 'clinic-only', 'USERPCARE' => 'clinic-user']), '1');
        $this->assertSame('clinic-only', PcareConfiguration::findOrFail(1)->CONSIDAPIPCARE);
        $this->assertSame('consumer', DB::connection('central')->table('config_pcare')->value('CONSIDAPIPCARE'));
        $controller->destroy($this->requestData([]), '1');
        $this->assertSame(0, PcareConfiguration::count());
        $this->assertSame(1, DB::connection('central')->table('config_pcare')->count());
    }

    public function test_crud_preserves_blank_secrets_and_uses_exact_table_columns(): void
    {
        $controller = new PcareConfigurationController;
        $controller->store($this->requestData($this->credentials()));
        $this->assertSame('secret', PcareConfiguration::findOrFail(1)->SECRETKEYAPIPCARE);
        $controller->update($this->requestData(['CONSIDAPIPCARE' => 'updated', 'USERPCARE' => 'updated-user', 'SECRETKEYAPIPCARE' => '', 'USERKEYAPIPCARE' => '', 'PASSPCARE' => 'new-pass']), '1');
        $row = PcareConfiguration::findOrFail(1);
        $this->assertSame('updated', $row->CONSIDAPIPCARE);
        $this->assertSame('secret', $row->SECRETKEYAPIPCARE);
        $this->assertSame('key', $row->USERKEYAPIPCARE);
        $this->assertSame('new-pass', $row->PASSPCARE);
        $this->assertArrayNotHasKey('PASSPCARE', $row->toArray());
        $controller->destroy($this->requestData([]), '1');
        $this->assertSame(0, PcareConfiguration::count());
    }

    public function test_duplicate_id_is_rejected_without_flashing_secrets(): void
    {
        $controller = new PcareConfigurationController;
        $controller->store($this->requestData($this->credentials()));
        try {
            $controller->store($this->requestData($this->credentials()));
            $this->fail('Duplicate ID was accepted.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('id', $e->errors());
            $this->assertArrayNotHasKey('SECRETKEYAPIPCARE', session()->get('_old_input', []));
        }
        $this->assertSame(1, PcareConfiguration::count());
    }

    public function test_non_superadmin_cannot_create_configuration(): void
    {
        try {
            (new PcareConfigurationController)->store($this->requestData($this->credentials(), false));
            $this->fail('Non-superadmin was allowed.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame(0, PcareConfiguration::count());
    }
}
