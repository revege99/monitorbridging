<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\ClinicDatabase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class SetupClinicController extends Controller
{
    private function authorizeSuperadmin(Request $request): void
    {
        abort_unless($request->user()->isSuperadmin(), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeSuperadmin($request);

        return view('setup.index', [
            'tab' => $request->query('tab', 'clinics'),
            'clinics' => Clinic::with('database')->get(),
            'users' => User::orderBy('name')->get(),
            'nextClinicCode' => 'KLN-0001',
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    public function storeClinic(Request $request)
    {
        $this->authorizeSuperadmin($request);
        abort_if(Clinic::exists(), 422, 'Profil klinik aplikasi sudah ada. Gunakan Edit Profil.');
        $clinic = new Clinic($this->profileData($request));
        $clinic->id = 1;
        $clinic->save();

        return back()->with('success', 'Profil klinik aplikasi disimpan.');
    }

    public function updateClinic(Request $request, Clinic $clinic)
    {
        $this->authorizeSuperadmin($request);
        $clinic->update($this->profileData($request));

        return back()->with('success', 'Profil klinik aplikasi diperbarui.');
    }

    private function profileData(Request $request): array
    {
        if (blank($request->input('code'))) {
            $request->merge(['code' => 'KLN-0001']);
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'], 'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'], 'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'], 'is_active' => ['boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    public function saveDatabase(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $data = $request->validate([
            'connection_name' => ['required', 'string', 'max:255'], 'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'], 'database_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'], 'password' => ['nullable', 'string'],
            'collation' => ['required', 'string', 'max:50'],
        ]);
        $existing = ClinicDatabase::find(1);
        if ($existing && blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        ClinicDatabase::updateOrCreate(['id' => 1], $data + ['driver' => 'mariadb', 'charset' => 'utf8mb4']);

        return back()->with('success', 'Koneksi SIMRS aplikasi disimpan. Restart worker yang sedang berjalan.');
    }

    public function testDatabase(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $database = ClinicDatabase::findOrFail(1);
        config(['database.connections.clinic_test' => ['driver' => $database->driver, 'host' => $database->host,
            'port' => $database->port, 'database' => $database->database_name, 'username' => $database->username,
            'password' => $database->password, 'charset' => $database->charset, 'collation' => $database->collation,
            'prefix' => '', 'strict' => true]]);
        try {
            DB::purge('clinic_test');
            DB::connection('clinic_test')->getPdo();

            return back()->with('success', 'Koneksi database berhasil.');
        } catch (Throwable $e) {
            return back()->withErrors(['database' => 'Koneksi SIMRS gagal. Periksa host, port, nama database dan kredensial.']);
        } finally {
            DB::disconnect('clinic_test');
        }
    }

    public function storeUser(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $data = $this->userData($request);
        User::create($data + ['is_active' => true]);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $user)
    {
        $this->authorizeSuperadmin($request);
        $data = $this->userData($request, $user);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $data['is_active'] = $request->boolean('is_active');
        $user->update($data);

        return back()->with('success', 'User diperbarui.');
    }

    private function userData(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:60', Rule::unique('central.users', 'username')->ignore($user?->id)],
            'email' => ['required', 'email', Rule::unique('central.users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed'],
            'role' => ['required', Rule::in(['superadmin', 'admin'])], 'is_active' => ['boolean'],
        ]);
    }

    public function deleteUser(Request $request, User $user)
    {
        $this->authorizeSuperadmin($request);
        abort_if($user->is($request->user()), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');
        $user->delete();

        return back()->with('success', 'User dihapus.');
    }
}
