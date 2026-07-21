@php($editing = isset($configuration) && $configuration)
<div class="grid gap-4 sm:grid-cols-2">
    <label class="text-xs font-medium text-slate-600 sm:col-span-2">Klinik<select name="clinic_id" required class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm">@foreach($clinics as $clinic)<option value="{{ $clinic->id }}" @selected(old('clinic_id', $configuration?->clinic_id) == $clinic->id)>{{ $clinic->name }}</option>@endforeach</select></label>
    <label class="text-xs font-medium text-slate-600">Client ID<input name="client_id" required value="{{ old('client_id', $configuration?->client_id) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm"></label>
    <label class="text-xs font-medium text-slate-600">Client Secret<input type="password" name="client_secret" {{ $editing ? '' : 'required' }} placeholder="{{ $editing ? 'Kosongkan jika tidak diubah' : '' }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm"></label>
    <label class="text-xs font-medium text-slate-600">Organization ID<input name="organization_id" required value="{{ old('organization_id', $configuration?->organization_id) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm"></label>
    <label class="text-xs font-medium text-slate-600">Location ID<input name="location_id" required value="{{ old('location_id', $configuration?->location_id) }}" class="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-sm"></label>
    <div class="flex justify-end gap-2 sm:col-span-2"><button type="button" data-modal-close class="rounded-xl border px-4 text-sm">Batal</button><button class="rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white">Simpan</button></div>
</div>
