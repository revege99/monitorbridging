@php($clinic = $clinic ?? null)
<div class="grid gap-4 sm:grid-cols-2">
@foreach(['code'=>'Kode Klinik *','name'=>'Nama Klinik *','legal_name'=>'Nama Legal','phone'=>'Telepon','email'=>'Email','city'=>'Kota','province'=>'Provinsi','postal_code'=>'Kode Pos'] as $name=>$label)
<label class="text-xs font-medium text-slate-600">{{ $label }}<input name="{{ $name }}" value="{{ old($name, $clinic?->{$name} ?? ($name === 'code' ? $nextClinicCode : null)) }}" {{ in_array($name,['code','name'])?'required':'' }} class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-cyan-400">@if($name === 'code' && !$clinic)<span class="mt-1 block text-[11px] font-normal text-slate-400">Dibuat otomatis dan tetap dapat diubah.</span>@endif</label>
@endforeach
<label class="text-xs font-medium text-slate-600 sm:col-span-2">Alamat<textarea name="address" rows="3" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-cyan-400">{{ old('address',$clinic?->address) }}</textarea></label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$clinic?->is_active ?? true))> Klinik aktif</label>
<div class="flex justify-end gap-2 sm:col-span-2"><button type="button" data-modal-close class="rounded-xl border px-4 py-2.5 text-sm">Batal</button><button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Simpan</button></div>
</div>
