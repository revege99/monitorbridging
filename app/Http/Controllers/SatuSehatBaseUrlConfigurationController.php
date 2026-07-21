<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\SatuSehatBaseUrlConfiguration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SatuSehatBaseUrlConfigurationController extends Controller
{
    private function authorizeSuperadmin(Request $request): void { abort_unless($request->user()->isSuperadmin(), 403); }
    public function index(Request $request)
    {
        $this->authorizeSuperadmin($request);
        return view('configurations.satu-sehat-base-url.index', [
            'configurations' => SatuSehatBaseUrlConfiguration::get(),
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }
    public function store(Request $request)
    {
        $this->authorizeSuperadmin($request);
        abort_if(SatuSehatBaseUrlConfiguration::exists(), 422, 'Base URL Satu Sehat sudah tersedia. Silakan gunakan Edit.');
        SatuSehatBaseUrlConfiguration::create($this->validated($request));
        return back()->with('success', 'Base URL Satu Sehat berhasil ditambahkan.');
    }
    public function update(Request $request, SatuSehatBaseUrlConfiguration $satuSehatBaseUrlConfiguration)
    {
        $this->authorizeSuperadmin($request);
        $satuSehatBaseUrlConfiguration->update($this->validated($request, $satuSehatBaseUrlConfiguration));
        return back()->with('success', 'Base URL Satu Sehat berhasil diperbarui.');
    }
    public function destroy(Request $request, SatuSehatBaseUrlConfiguration $satuSehatBaseUrlConfiguration)
    {
        $this->authorizeSuperadmin($request);
        $satuSehatBaseUrlConfiguration->delete();
        return back()->with('success', 'Base URL Satu Sehat berhasil dihapus.');
    }
    private function validated(Request $request, ?SatuSehatBaseUrlConfiguration $configuration = null): array
    {
        return $request->validate([
            'auth_url' => ['required', 'url:http,https', 'max:2048'],
            'fhir_url' => ['required', 'url:http,https', 'max:2048'],
        ]);
    }
}
