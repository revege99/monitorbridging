<div class="pcare-form">
    <div class="pcare-form-intro"><h4>Alamat layanan</h4><p>Masukkan URL lengkap untuk integrasi Satu Sehat.</p></div>
    <div class="grid gap-4">
    <label class="text-xs font-medium text-slate-600">Auth URL<input type="url" name="auth_url" required placeholder="https://..." value="{{ old('auth_url', $configuration?->auth_url) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm"></label>
    <label class="text-xs font-medium text-slate-600">FHIR URL<input type="url" name="fhir_url" required placeholder="https://..." value="{{ old('fhir_url', $configuration?->fhir_url) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm"></label>
    </div><div class="pcare-form-hint">Gunakan alamat lengkap dengan awalan https:// atau http://. Konfigurasi ini digunakan seluruh klinik.</div><div class="pcare-form-footer"><button type="button" data-modal-close class="rounded-xl border px-4 text-sm">Batal</button><button class="rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white">{{ $configuration ? 'Simpan Perubahan' : 'Simpan Base URL' }}</button></div>
</div>
