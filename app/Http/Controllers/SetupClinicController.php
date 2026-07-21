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
    private function authorizeSuperadmin(Request $request): void { abort_unless($request->user()->isSuperadmin(), 403); }

    public function index(Request $request)
    {
        $this->authorizeSuperadmin($request);
        return view('setup.index', [
            'tab' => $request->query('tab', 'clinics'),
            'clinics' => Clinic::with('database')->orderBy('name')->get(),
            'users' => User::with('clinic')->orderBy('name')->get(),
            'nextClinicCode' => $this->nextClinicCode(),
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    public function storeClinic(Request $request)
    {
        $this->authorizeSuperadmin($request);
        if (blank($request->input('code'))) {
            $request->merge(['code' => $this->nextClinicCode()]);
        }
        Clinic::create($request->validate([
            'code' => ['required', 'max:30', 'unique:central.clinics,code'], 'name' => ['required', 'max:255'],
            'legal_name' => ['nullable', 'max:255'], 'phone' => ['nullable', 'max:30'], 'email' => ['nullable', 'email'],
            'address' => ['nullable'], 'city' => ['nullable', 'max:100'], 'province' => ['nullable', 'max:100'],
            'postal_code' => ['nullable', 'max:10'], 'is_active' => ['boolean'],
        ]) + ['is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Profil klinik berhasil ditambahkan.');
    }

    private function nextClinicCode(): string
    {
        $number = ((int) Clinic::max('id')) + 1;
        do {
            $code = 'KLN-' . str_pad((string) $number++, 4, '0', STR_PAD_LEFT);
        } while (Clinic::where('code', $code)->exists());

        return $code;
    }

    public function updateClinic(Request $request, Clinic $clinic)
    {
        $this->authorizeSuperadmin($request);
        $clinic->update($request->validate([
            'code' => ['required', 'max:30', Rule::unique('central.clinics', 'code')->ignore($clinic->id)], 'name' => ['required'],
            'legal_name' => ['nullable'], 'phone' => ['nullable'], 'email' => ['nullable', 'email'], 'address' => ['nullable'],
            'city' => ['nullable'], 'province' => ['nullable'], 'postal_code' => ['nullable'], 'is_active' => ['boolean'],
        ]) + ['is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Profil klinik diperbarui.');
    }

    public function deleteClinic(Request $request, Clinic $clinic)
    {
        $this->authorizeSuperadmin($request);
        abort_if($clinic->users()->exists(), 422, 'Klinik masih digunakan oleh user.');
        $clinic->delete();
        return back()->with('success', 'Klinik dihapus.');
    }

    public function saveDatabase(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $data = $request->validate([
            'clinic_id' => ['required', 'exists:central.clinics,id'], 'connection_name' => ['required'],
            'host' => ['required'], 'port' => ['required', 'integer'], 'database_name' => ['required'],
            'username' => ['required'], 'password' => ['nullable'], 'collation' => ['required'],
        ]);
        $existing = ClinicDatabase::where('clinic_id', $data['clinic_id'])->first();
        if ($existing && blank($data['password'])) unset($data['password']);
        ClinicDatabase::updateOrCreate(['clinic_id' => $data['clinic_id']], $data + ['driver' => 'mariadb', 'charset' => 'utf8mb4']);
        return back()->with('success', 'Konfigurasi database disimpan.');
    }

    public function testDatabase(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $database = ClinicDatabase::where('clinic_id', $request->validate(['clinic_id' => ['required']])['clinic_id'])->firstOrFail();
        config(['database.connections.clinic_test' => ['driver' => $database->driver, 'host' => $database->host, 'port' => $database->port, 'database' => $database->database_name, 'username' => $database->username, 'password' => $database->password, 'charset' => $database->charset, 'collation' => $database->collation, 'prefix' => '', 'strict' => true]]);
        try { DB::purge('clinic_test'); DB::connection('clinic_test')->getPdo(); return back()->with('success', 'Koneksi database berhasil.'); }
        catch (Throwable $e) { return back()->withErrors(['database' => 'Koneksi gagal: '.$e->getMessage()]); }
    }

    public function storeUser(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $data = $request->validate(['name' => ['required'], 'username' => ['required', 'alpha_dash', 'max:60', 'unique:central.users,username'], 'email' => ['required', 'email', 'unique:central.users,email'], 'password' => ['required', 'confirmed'], 'role' => ['required', Rule::in(['superadmin', 'admin'])], 'clinic_id' => ['nullable', 'exists:central.clinics,id']]);
        if ($data['role'] === 'admin' && empty($data['clinic_id'])) return back()->withErrors(['clinic_id' => 'Admin wajib memiliki klinik.']);
        User::create($data + ['is_active' => true]);
        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $user)
    {
        $this->authorizeSuperadmin($request);
        $data = $request->validate(['name' => ['required'], 'username' => ['required', 'alpha_dash', 'max:60', Rule::unique('central.users', 'username')->ignore($user->id)], 'email' => ['required', 'email', Rule::unique('central.users', 'email')->ignore($user->id)], 'password' => ['nullable', 'confirmed'], 'role' => ['required', Rule::in(['superadmin', 'admin'])], 'clinic_id' => ['nullable', 'exists:central.clinics,id'], 'is_active' => ['boolean']]);
        if (blank($data['password'] ?? null)) unset($data['password']);
        $data['clinic_id'] = $data['role'] === 'superadmin' ? null : ($data['clinic_id'] ?? null);
        if ($data['role'] === 'admin' && empty($data['clinic_id'])) return back()->withErrors(['clinic_id' => 'Admin wajib memiliki klinik.']);
        $data['is_active'] = $request->boolean('is_active');
        $user->update($data);
        return back()->with('success', 'User diperbarui.');
    }

    public function deleteUser(Request $request, User $user)
    {
        $this->authorizeSuperadmin($request);
        abort_if($user->is($request->user()), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');
        $user->delete();
        return back()->with('success', 'User dihapus.');
    }

    public function selectClinic(Request $request)
    {
        abort_unless($request->user()->isSuperadmin(), 403);
        $clinicId = $request->validate(['clinic_id' => ['required', 'exists:central.clinics,id']])['clinic_id'];
        $request->session()->put('active_clinic_id', $clinicId);
        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return back();
    }
}
