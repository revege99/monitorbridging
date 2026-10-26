@php($editing = isset($configuration) && $configuration)
<div class="pcare-form">
    <div class="pcare-form-intro"><h4>Detail konfigurasi</h4><p>Isi identitas dan kredensial yang digunakan untuk terhubung ke PCare.</p></div>
    <div class="pcare-form-grid">
    <label class="text-xs font-medium text-slate-600">ID
        <input type="number" name="id" min="0" max="255" required value="{{ $editing ? $configuration->id : old('id', 1) }}" @readonly($editing) class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
    </label>
    @foreach(['CONSIDAPIPCARE' => 'Consumer ID', 'USERPCARE' => 'Username'] as $name => $label)
        <label class="text-xs font-medium text-slate-600">{{ $label }}
            <input name="{{ $name }}" required value="{{ old($name, $configuration?->{$name}) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
        </label>
    @endforeach
    @foreach(['SECRETKEYAPIPCARE' => 'Secret Key', 'USERKEYAPIPCARE' => 'User Key PCare', 'PASSPCARE' => 'Password'] as $name => $label)
        <label class="text-xs font-medium text-slate-600">{{ $label }}
            <input type="text" name="{{ $name }}" autocomplete="off" value="{{ $configuration?->{$name} }}" {{ $editing ? '' : 'required' }} placeholder="{{ $editing ? 'Tidak diubah' : '' }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
        </label>
    @endforeach
    </div>
    @if($editing)<div class="pcare-form-hint">Key dan password yang dikosongkan akan tetap menggunakan nilai sebelumnya.</div>@endif
    <div class="pcare-form-footer">
        <button type="button" data-modal-close class="rounded-xl border px-4 py-2.5 text-sm">Batal</button>
        <button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">{{ $editing ? 'Simpan Perubahan' : 'Simpan Kredensial' }}</button>
    </div>
</div>
