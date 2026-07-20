@php($database = $selectedClinic?->database)
<div class="grid gap-4 sm:grid-cols-2">
<label class="text-xs font-medium sm:col-span-2">Klinik<select name="clinic_id" required class="mt-1.5 w-full rounded-xl border p-2.5 text-sm" @disabled($selectedClinic)>@foreach($clinics as $clinic)<option value="{{ $clinic->id }}" @selected($selectedClinic?->id===$clinic->id)>{{ $clinic->name }}</option>@endforeach</select>@if($selectedClinic)<input type="hidden" name="clinic_id" value="{{ $selectedClinic->id }}">@endif</label>
@foreach(['connection_name'=>'Nama Server','host'=>'Host','port'=>'Port','database_name'=>'Nama Database','username'=>'Username','password'=>'Password','collation'=>'Collation'] as $name=>$label)
<label class="text-xs font-medium">{{ $label }}<input name="{{ $name }}" type="{{ $name==='password'?'password':'text' }}" value="{{ $name==='password' ? '' : old($name,$database?->{$name} ?? ($name==='port'?3306:($name==='collation'?'utf8mb4_unicode_ci':($name==='connection_name'?'SIMRS':'')))) }}" placeholder="{{ $name==='password' && $database ? 'Kosongkan jika tidak diubah' : '' }}" {{ $name==='password'?'':'required' }} class="mt-1.5 w-full rounded-xl border p-2.5 text-sm"></label>
@endforeach
<label class="text-xs font-medium">Driver<input value="MariaDB" disabled class="mt-1.5 w-full rounded-xl border bg-slate-50 p-2.5 text-sm"></label>
<div class="flex justify-end gap-2 sm:col-span-2"><button type="button" data-modal-close class="rounded-xl border px-4 py-2.5 text-sm">Batal</button><button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Simpan</button></div>
</div>
