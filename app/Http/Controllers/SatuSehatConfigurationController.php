<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\SatuSehatConfiguration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SatuSehatConfigurationController extends Controller
{
    private function authorizeSuperadmin(Request $request): void { abort_unless($request->user()->isSuperadmin(), 403); }

    public function index(Request $request)
    {
        $this->authorizeSuperadmin($request);
        return view('configurations.satu-sehat.index', [
            'clinics' => Clinic::with('satuSehatConfiguration')->orderBy('name')->get(),
            'configurations' => SatuSehatConfiguration::with('clinic')->orderBy('clinic_id')->get(),
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperadmin($request);
        SatuSehatConfiguration::create($this->validated($request));
        return back()->with('success', 'Konfigurasi Satu Sehat berhasil ditambahkan.');
    }

    public function update(Request $request, SatuSehatConfiguration $satuSehatConfiguration)
    {
        $this->authorizeSuperadmin($request);
        $data = $this->validated($request, $satuSehatConfiguration);
        if (blank($data['client_secret'] ?? null)) unset($data['client_secret']);
        $satuSehatConfiguration->update($data);
        return back()->with('success', 'Konfigurasi Satu Sehat berhasil diperbarui.');
    }

    public function destroy(Request $request, SatuSehatConfiguration $satuSehatConfiguration)
    {
        $this->authorizeSuperadmin($request);
        $satuSehatConfiguration->delete();
        return back()->with('success', 'Konfigurasi Satu Sehat berhasil dihapus.');
    }

    private function validated(Request $request, ?SatuSehatConfiguration $configuration = null): array
    {
        return $request->validate([
            'clinic_id' => ['required', 'exists:central.clinics,id', Rule::unique('central.satu_sehat_configurations', 'clinic_id')->ignore($configuration?->id)],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => $configuration ? ['nullable', 'string'] : ['required', 'string'],
            'organization_id' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'string', 'max:255'],
        ]);
    }
}
