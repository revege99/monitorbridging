<?php

namespace Tests\Feature;

use App\Http\Controllers\BpjsConfigurationController;
use App\Http\Controllers\SetupClinicController;
use App\Models\BpjsConfiguration;
use App\Models\Clinic;
use App\Models\ClinicDatabase;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SingleClinicConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.central' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('central');
        (require database_path('migrations/2026_11_12_000001_use_single_clinic_application_configuration.php'))->up();
        Schema::connection('central')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->boolean('is_active');
            $table->timestamps();
        });
    }

    private function request(array $data): Request
    {
        $request = Request::create('/setup-klinik', 'POST', $data);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => new User(['role' => 'superadmin', 'is_active' => true]));

        return $request;
    }

    public function test_profile_database_and_admin_save_without_clinic_id(): void
    {
        $controller = new SetupClinicController;
        $controller->storeClinic($this->request(['name' => 'Klinik Lokal', 'is_active' => true]));
        $database = ['connection_name' => 'SIMRS', 'host' => '127.0.0.1', 'port' => 3306,
            'database_name' => 'sik', 'username' => 'test', 'password' => 'db-secret', 'collation' => 'utf8mb4_unicode_ci'];
        $controller->saveDatabase($this->request($database));
        $database['password'] = '';
        $controller->saveDatabase($this->request($database));
        $this->assertSame('db-secret', ClinicDatabase::first()->password);
        $this->assertSame('sik', Clinic::first()->database->database_name);
        $this->assertDatabaseCount('application_database_configurations', 1, 'central');
        $controller->storeUser($this->request(['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@example.test',
            'password' => 'test-password', 'password_confirmation' => 'test-password', 'role' => 'admin']));
        $this->assertDatabaseHas('users', ['username' => 'admin', 'role' => 'admin'], 'central');
    }

    public function test_bpjs_is_singleton_and_preserves_encrypted_secrets_without_clinic_id(): void
    {
        $controller = new BpjsConfigurationController;
        $data = ['cons_id' => 'consumer', 'secret_key' => 'secret', 'user_key_antrol' => 'antrol',
            'user_key_pcare' => 'pcare', 'kode_aplikasi' => '095', 'username' => 'user', 'password' => 'password'];
        $controller->store($this->request($data));
        $this->assertSame('secret', BpjsConfiguration::first()->secret_key);
        $this->assertNotSame('secret', DB::connection('central')->table('application_bpjs_configurations')->value('secret_key'));
        $data['secret_key'] = '';
        $controller->update($this->request($data), BpjsConfiguration::first());
        $this->assertSame('secret', BpjsConfiguration::first()->secret_key);
        $this->assertFalse(Schema::connection('central')->hasColumn('application_bpjs_configurations', 'clinic_id'));
        try {
            $controller->store($this->request($data));
            $this->fail('Second BPJS configuration was accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
        $this->assertDatabaseCount('application_bpjs_configurations', 1, 'central');
    }

    public function test_setup_renders_without_clinic_selector_or_activation_and_rejects_second_profile(): void
    {
        $controller = new SetupClinicController;
        $controller->storeClinic($this->request(['name' => 'Klinik Lokal', 'is_active' => true]));
        $this->actingAs(new User(['name' => 'Admin', 'email' => 'admin@example.test', 'role' => 'superadmin', 'is_active' => true]));
        view()->share('activeClinic', Clinic::first());
        $html = $controller->index($this->request([]))->render();
        $this->assertStringNotContainsString('name="clinic_id"', $html);
        $this->assertStringNotContainsString('Aktivasi Instalasi', $html);
        try {
            $controller->storeClinic($this->request(['name' => 'Other clinic']));
            $this->fail('Second profile was accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
        $this->assertDatabaseCount('application_profiles', 1, 'central');
    }

    public function test_database_test_form_renders_a_valid_csrf_token(): void
    {
        $controller = new SetupClinicController;
        $controller->storeClinic($this->request(['name' => 'Klinik Lokal', 'is_active' => true]));
        $controller->saveDatabase($this->request(['connection_name' => 'SIMRS', 'host' => '127.0.0.1',
            'port' => 3306, 'database_name' => 'sik', 'username' => 'test', 'password' => '',
            'collation' => 'utf8mb4_unicode_ci']));
        $this->actingAs(new User(['name' => 'Admin', 'email' => 'admin@example.test', 'role' => 'superadmin', 'is_active' => true]));
        view()->share('activeClinic', Clinic::first());
        session()->regenerateToken();
        $request = $this->request([]);
        $request->query->set('tab', 'database');
        $html = $controller->index($request)->render();
        $document = new \DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $tokens = $xpath->query('//form[@action="'.route('setup.database.test').'"]//input[@name="_token"]');
        $this->assertSame(1, $tokens->length);
        $this->assertNotEmpty($tokens->item(0)->getAttribute('value'));
        $this->assertSame(csrf_token(), $tokens->item(0)->getAttribute('value'));
        $this->assertStringNotContainsString('@csrfid', $html);
    }

    public function test_unreadable_bpjs_credentials_can_be_reentered_without_hiding_the_failure(): void
    {
        $controller = new BpjsConfigurationController;
        $data = ['cons_id' => 'consumer', 'secret_key' => 'healthy-secret', 'user_key_antrol' => 'antrol',
            'user_key_pcare' => 'pcare', 'kode_aplikasi' => '095', 'username' => 'user', 'password' => 'password'];
        $controller->store($this->request($data));
        DB::connection('central')->table('application_bpjs_configurations')->update(['user_key_antrol' => 'invalid-plaintext-or-damaged-payload']);
        $configuration = BpjsConfiguration::first();
        $html = view('configurations.bpjs.form', compact('configuration'))->render();
        $this->assertStringContainsString('tidak dapat didekripsi: user_key_antrol', $html);
        $this->assertStringContainsString('healthy-secret', $html);
        $this->assertStringNotContainsString('invalid-plaintext-or-damaged-payload', $html);
        $this->assertSame('invalid-plaintext-or-damaged-payload', $configuration->getRawOriginal('user_key_antrol'));
        $data['secret_key'] = '';
        $data['user_key_antrol'] = '';
        try {
            $controller->update($this->request($data), $configuration);
            $this->fail('Unreadable credential must be replaced before saving.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('user_key_antrol', $exception->errors());
            $this->assertArrayNotHasKey('password', session()->get('_old_input', []));
        }
        $data['user_key_antrol'] = 'reentered-antrol';
        $controller->update($this->request($data), $configuration);
        $this->assertSame('reentered-antrol', $configuration->fresh()->user_key_antrol);
        $this->assertSame('healthy-secret', $configuration->fresh()->secret_key);
    }
}
