<div class="grid gap-4">
    <label class="text-xs font-medium text-slate-600">Auth URL<input type="url" name="auth_url" required placeholder="https://..." value="{{ old('auth_url', $configuration?->auth_url) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm"></label>
    <label class="text-xs font-medium text-slate-600">FHIR URL<input type="url" name="fhir_url" required placeholder="https://..." value="{{ old('fhir_url', $configuration?->fhir_url) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm"></label>
    <div class="flex justify-end gap-2"><button type="button" data-modal-close class="rounded-xl border px-4 text-sm">Batal</button><button class="rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white">Simpan</button></div>
</div>
