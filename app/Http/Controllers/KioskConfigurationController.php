<?php

namespace App\Http\Controllers;

use App\Models\KioskConfiguration;
use App\Services\MedicalRecordFormat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KioskConfigurationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperadmin(), 403);
        $configuration = KioskConfiguration::current();

        return view('configurations.kiosk.index', [
            'configuration' => $configuration, 'preview' => (new MedicalRecordFormat($configuration))->format(1),
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->isSuperadmin(), 403);
        $data = $request->validate([
            'rm_digits' => ['required', 'integer', 'between:1,12'],
            'rm_use_separator' => ['required', 'boolean'],
            'rm_separator' => ['required', Rule::in(['-', '.', '/'])],
            'rm_groups' => ['nullable', 'string', 'max:30', 'regex:/^[1-9][0-9]*(?:-[1-9][0-9]*)*$/D'],
        ]);
        $groups = explode('-', $data['rm_groups'] ?? '');
        if ($request->boolean('rm_use_separator') &&
            (array_sum(array_map('intval', $groups)) !== (int) $data['rm_digits'] ||
                (int) $data['rm_digits'] + count($groups) - 1 > 15)) {
            throw ValidationException::withMessages(['rm_groups' => 'Jumlah pengelompokan harus sama dengan jumlah digit, dan panjang hasil maksimal 15 karakter.']);
        }
        if (! $request->boolean('rm_use_separator') && array_sum(array_map('intval', $groups)) !== (int) $data['rm_digits']) {
            $data['rm_groups'] = (string) $data['rm_digits'];
        }
        KioskConfiguration::updateOrCreate(['id' => 1], $data);

        return back()->with('success', 'Pengaturan Anjungan disimpan. Format berlaku untuk nomor RM pasien baru.');
    }
}
