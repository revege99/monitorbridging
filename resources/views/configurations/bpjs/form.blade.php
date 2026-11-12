@php($editing = isset($configuration) && $configuration)
@php($credentialState = $configuration?->formCredentials() ?? ['values' => [], 'invalid' => []])
<div class="pcare-form">
    @if($credentialState['invalid'])
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800" role="alert">Kredensial berikut kosong atau tidak dapat didekripsi: {{ implode(', ', $credentialState['invalid']) }}. Isi ulang kolom tersebut dengan kredensial BPJS yang benar, lalu simpan. Nilai yang masih valid tetap dipertahankan.</div>
    @endif
    <div class="pcare-form-intro"><h4>Detail konfigurasi BPJS</h4><p>Lengkapi kredensial BPJS untuk aplikasi ini.</p></div>
    <div class="pcare-form-grid">
<label class="text-xs font-medium text-slate-600">Consumer ID
        <input name="cons_id" required value="{{ old('cons_id', $configuration?->cons_id) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
    </label>
    <label class="text-xs font-medium text-slate-600">Kode Aplikasi
        <input name="kode_aplikasi" required value="{{ old('kode_aplikasi', $configuration?->kode_aplikasi ?? '095') }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
    </label>
    @foreach(['secret_key' => 'Secret Key', 'user_key_antrol' => 'User Key Antrol', 'user_key_pcare' => 'User Key PCare'] as $name => $label)
        <label class="text-xs font-medium text-slate-600 {{ $name === 'secret_key' ? 'sm:col-span-2' : '' }}">{{ $label }}
            <input type="text" autocomplete="off" name="{{ $name }}" value="{{ $credentialState['values'][$name] ?? '' }}" {{ !$editing || in_array($name, $credentialState['invalid'], true) ? 'required' : '' }} class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
        </label>
    @endforeach
    <label class="text-xs font-medium text-slate-600">Username
        <input name="username" required value="{{ old('username', $configuration?->username) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
    </label>
    <label class="text-xs font-medium text-slate-600">Password
        <input type="text" autocomplete="off" name="password" value="{{ $credentialState['values']['password'] ?? '' }}" {{ !$editing || in_array('password', $credentialState['invalid'], true) ? 'required' : '' }} class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
    </label>
    </div>
    @if($editing)<div class="pcare-form-hint">Key dan password valid yang dikosongkan tetap menggunakan nilai sebelumnya. Kolom yang tidak dapat dibaca wajib diisi ulang.</div>@endif
    <div class="pcare-form-footer">
        <button type="button" data-modal-close class="rounded-xl border px-4 py-2.5 text-sm">Batal</button>
        <button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">{{ $editing ? 'Simpan Perubahan' : 'Simpan Kredensial' }}</button>
    </div>
</div>
