<div class="grid gap-4">
    <label class="text-xs font-medium text-slate-600">Base URL PCare<input type="url" name="base_url_pcare" required placeholder="https://..." value="{{ old('base_url_pcare', $configuration?->base_url_pcare) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm"></label>
    <label class="text-xs font-medium text-slate-600">Base URL Antrean<input type="url" name="base_url_antrean" required placeholder="https://..." value="{{ old('base_url_antrean', $configuration?->base_url_antrean) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm"></label>
    <div class="flex justify-end gap-2"><button type="button" data-modal-close class="rounded-xl border px-4 text-sm">Batal</button><button class="rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white">Simpan</button></div>
</div>
