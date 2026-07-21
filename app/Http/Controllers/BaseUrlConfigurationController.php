<?php

namespace App\Http\Controllers;

use App\Models\BaseUrlConfiguration;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BaseUrlConfigurationController extends Controller
{
    private function authorizeSuperadmin(Request $request): void { abort_unless($request->user()->isSuperadmin(), 403); }

    public function index(Request $request)
    {
        $this->authorizeSuperadmin($request);
        return view('configurations.base-url.index', [
            'configurations' => BaseUrlConfiguration::get(),
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperadmin($request);
        abort_if(BaseUrlConfiguration::exists(), 422, 'Konfigurasi Base URL BPJS sudah tersedia. Silakan gunakan Edit.');
        BaseUrlConfiguration::create($this->validated($request));
        return back()->with('success', 'Konfigurasi Base URL berhasil ditambahkan.');
    }

    public function update(Request $request, BaseUrlConfiguration $baseUrlConfiguration)
    {
        $this->authorizeSuperadmin($request);
        $baseUrlConfiguration->update($this->validated($request, $baseUrlConfiguration));
        return back()->with('success', 'Konfigurasi Base URL berhasil diperbarui.');
    }

    public function destroy(Request $request, BaseUrlConfiguration $baseUrlConfiguration)
    {
        $this->authorizeSuperadmin($request);
        $baseUrlConfiguration->delete();
        return back()->with('success', 'Konfigurasi Base URL berhasil dihapus.');
    }

    private function validated(Request $request, ?BaseUrlConfiguration $configuration = null): array
    {
        return $request->validate([
            'base_url_pcare' => ['required', 'url:http,https', 'max:2048'],
            'base_url_antrean' => ['required', 'url:http,https', 'max:2048'],
        ]);
    }
}
