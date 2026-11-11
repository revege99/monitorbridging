<?php

namespace App\Http\Controllers;

use App\Models\SatuSehatConfiguration;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SatuSehatConfigurationController extends Controller
{
    private function authorizeSuperadmin(Request $request): void
    {
        abort_unless($request->user()->isSuperadmin(), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $configurationNotice = null;
        $configurationUnavailable = false;
        try {
            $configurations = SatuSehatConfiguration::orderBy('id')->get();
        } catch (QueryException $exception) {
            $configurations = collect();
            $configurationUnavailable = true;
            $configurationNotice = 'Konfigurasi Satu Sehat belum dapat dimuat. Periksa koneksi database klinik dan ketersediaan tabel ss_config.';
        }

        return view('configurations.satu-sehat.index', compact('configurations', 'configurationNotice', 'configurationUnavailable') + [
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $data = $this->validated($request);
        if (SatuSehatConfiguration::exists()) {
            $validator = validator([], []);
            $validator->errors()->add('client_id', 'Konfigurasi Satu Sehat klinik ini sudah ada. Gunakan Edit.');
            throw new ValidationException($validator, redirect()->back()->withErrors($validator)
                ->withInput($request->only(['client_id', 'organization_id', 'location_id'])));
        }
        SatuSehatConfiguration::create(['id' => 1] + $data);

        return back()->with('success', 'Konfigurasi Satu Sehat berhasil disimpan di database klinik.');
    }

    public function update(Request $request, string $satuSehatConfiguration)
    {
        $this->authorizeSuperadmin($request);
        // Resolve setelah middleware menyiapkan koneksi database klinik terpilih.
        $configuration = SatuSehatConfiguration::findOrFail($satuSehatConfiguration);
        $data = $this->validated($request, $configuration);
        if (blank($data['client_secret'] ?? null)) {
            unset($data['client_secret']);
        }
        $configuration->update($data);

        return back()->with('success', 'Konfigurasi Satu Sehat berhasil diperbarui di database klinik.');
    }

    public function destroy(Request $request, string $satuSehatConfiguration)
    {
        $this->authorizeSuperadmin($request);
        SatuSehatConfiguration::findOrFail($satuSehatConfiguration)->delete();

        return back()->with('success', 'Konfigurasi Satu Sehat berhasil dihapus dari database klinik.');
    }

    private function validated(Request $request, ?SatuSehatConfiguration $configuration = null): array
    {
        $validator = validator($request->all(), [
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => [$configuration ? 'nullable' : 'required', 'string', 'max:16000'],
            'organization_id' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator, redirect()->back()->withErrors($validator)
                ->withInput($request->only(['client_id', 'organization_id', 'location_id'])));
        }

        return $validator->validated();
    }
}
