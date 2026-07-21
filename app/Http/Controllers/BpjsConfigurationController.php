<?php

namespace App\Http\Controllers;

use App\Models\BpjsConfiguration;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BpjsConfigurationController extends Controller
{
    private const SECRET_FIELDS = ['secret_key', 'user_key_antrol', 'user_key_pcare', 'password'];

    private function authorizeSuperadmin(Request $request): void
    {
        abort_unless($request->user()->isSuperadmin(), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeSuperadmin($request);

        return view('configurations.bpjs.index', [
            'clinics' => Clinic::with('bpjsConfiguration')->orderBy('name')->get(),
            'configurations' => BpjsConfiguration::with('clinic')->orderBy('clinic_id')->get(),
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperadmin($request);
        BpjsConfiguration::create($this->validated($request));

        return back()->with('success', 'Konfigurasi BPJS berhasil ditambahkan.');
    }

    public function update(Request $request, BpjsConfiguration $bpjsConfiguration)
    {
        $this->authorizeSuperadmin($request);
        $data = $this->validated($request, $bpjsConfiguration);
        foreach (self::SECRET_FIELDS as $field) {
            if (blank($data[$field] ?? null)) unset($data[$field]);
        }
        $bpjsConfiguration->update($data);

        return back()->with('success', 'Konfigurasi BPJS berhasil diperbarui.');
    }

    public function destroy(Request $request, BpjsConfiguration $bpjsConfiguration)
    {
        $this->authorizeSuperadmin($request);
        $bpjsConfiguration->delete();

        return back()->with('success', 'Konfigurasi BPJS berhasil dihapus.');
    }

    private function validated(Request $request, ?BpjsConfiguration $configuration = null): array
    {
        $secretRule = $configuration ? ['nullable', 'string'] : ['required', 'string'];

        return $request->validate([
            'clinic_id' => ['required', 'exists:central.clinics,id', Rule::unique('central.bpjs_configurations', 'clinic_id')->ignore($configuration?->id)],
            'cons_id' => ['required', 'string', 'max:255'],
            'secret_key' => $secretRule,
            'user_key_antrol' => $secretRule,
            'user_key_pcare' => $secretRule,
            'kode_aplikasi' => ['required', 'string', 'max:10'],
            'username' => ['required', 'string', 'max:255'],
            'password' => $secretRule,
        ]);
    }
}
