<?php

namespace App\Http\Controllers;

use App\Models\PcareConfiguration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PcareConfigurationController extends Controller
{
    private const SECRET_FIELDS = ['SECRETKEYAPIPCARE', 'USERKEYAPIPCARE', 'PASSPCARE'];

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
            $configurations = PcareConfiguration::orderBy('id')->get();
            if ($configurations->isEmpty()) {
                $configurationNotice = 'Belum ada konfigurasi PCare untuk klinik yang dipilih.';
            }
        } catch (\Illuminate\Database\QueryException $e) {
            report($e);
            $configurations = collect();
            $configurationUnavailable = true;
            $configurationNotice = 'Data PCare klinik yang dipilih belum dapat dimuat. Periksa koneksi database dan ketersediaan tabel config_pcare.';
        }
        return view('configurations.pcare.index', [
            'configurations' => $configurations,
            'configurationNotice' => $configurationNotice,
            'configurationUnavailable' => $configurationUnavailable,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperadmin($request);
        PcareConfiguration::create($this->validated($request));
        return back()->with('success', 'Config Pcare berhasil ditambahkan.');
    }

    public function update(Request $request, string $pcare)
    {
        $this->authorizeSuperadmin($request);
        // Resolve after the middleware selects the active clinic connection.
        $configuration = PcareConfiguration::findOrFail($pcare);
        $data = $this->validated($request, $configuration);
        foreach (self::SECRET_FIELDS as $field) {
            if (blank($data[$field] ?? null)) unset($data[$field]);
        }
        $configuration->update($data);
        return back()->with('success', 'Config Pcare berhasil diperbarui.');
    }

    public function destroy(Request $request, string $pcare)
    {
        $this->authorizeSuperadmin($request);
        PcareConfiguration::findOrFail($pcare)->delete();
        return back()->with('success', 'Config Pcare berhasil dihapus.');
    }

    private function validated(Request $request, ?PcareConfiguration $configuration = null): array
    {
        $secretRules = [$configuration ? 'nullable' : 'required', 'string', 'max:16000'];
        $rules = [
            'CONSIDAPIPCARE' => ['required', 'string', 'max:16000'],
            'USERPCARE' => ['required', 'string', 'max:16000'],
        ];
        if (!$configuration) {
            $rules['id'] = ['required', 'integer', 'between:0,255', Rule::unique('clinic.config_pcare', 'id')];
        }
        foreach (self::SECRET_FIELDS as $field) $rules[$field] = $secretRules;

        // Do not flash credentials to the session when validation fails.
        $validator = validator($request->all(), $rules);
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator, redirect()->back()
                ->withErrors($validator)->withInput($request->only(['id', 'CONSIDAPIPCARE', 'USERPCARE'])));
        }
        return $validator->validated();
    }
}
