<?php

namespace App\Http\Controllers;

use App\Models\BpjsConfiguration;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            'configurations' => BpjsConfiguration::get(),
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperadmin($request);
        abort_if(BpjsConfiguration::exists(), 422, 'Konfigurasi BPJS aplikasi sudah ada. Gunakan Edit.');
        BpjsConfiguration::create(['id' => 1] + $this->validated($request));

        return back()->with('success', 'Konfigurasi BPJS berhasil ditambahkan.');
    }

    public function update(Request $request, BpjsConfiguration $bpjsConfiguration)
    {
        $this->authorizeSuperadmin($request);
        $data = $this->validated($request, $bpjsConfiguration);
        foreach (self::SECRET_FIELDS as $field) {
            if (blank($data[$field] ?? null)) {
                unset($data[$field]);
            }
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
        $invalid = $configuration ? $configuration->formCredentials()['invalid'] : self::SECRET_FIELDS;
        $rules = [
            'cons_id' => ['required', 'string', 'max:255'],
            'kode_aplikasi' => ['required', 'string', 'max:10'],
            'username' => ['required', 'string', 'max:255'],
        ];
        foreach (self::SECRET_FIELDS as $field) {
            $rules[$field] = [in_array($field, $invalid, true) ? 'required' : 'nullable', 'string'];
        }
        $validator = validator($request->all(), $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator, redirect()->back()
                ->withErrors($validator)->withInput($request->only(['cons_id', 'kode_aplikasi', 'username'])));
        }

        return $validator->validated();
    }
}
