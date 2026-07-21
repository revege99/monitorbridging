@php($managedUser = $managedUser ?? null)
<div class="grid gap-4 sm:grid-cols-2">
<label class="text-xs font-medium">Nama *<input name="name" value="{{ old('name',$managedUser?->name) }}" required class="mt-1.5 w-full rounded-xl border p-2.5 text-sm"></label>
<label class="text-xs font-medium">Username Login *<input name="username" value="{{ old('username',$managedUser?->username) }}" required autocomplete="off" class="mt-1.5 w-full rounded-xl border p-2.5 text-sm" placeholder="Contoh: admin"></label>
<label class="text-xs font-medium">Email *<input name="email" type="email" value="{{ old('email',$managedUser?->email) }}" required class="mt-1.5 w-full rounded-xl border p-2.5 text-sm"></label>
<label class="text-xs font-medium">Role<select name="role" class="mt-1.5 w-full rounded-xl border p-2.5 text-sm"><option value="admin" @selected($managedUser?->role==='admin')>Admin</option><option value="superadmin" @selected($managedUser?->role==='superadmin')>Superadmin</option></select></label>
<label class="text-xs font-medium">Klinik<select name="clinic_id" class="mt-1.5 w-full rounded-xl border p-2.5 text-sm"><option value="">Semua Klinik (Superadmin)</option>@foreach($clinics as $clinic)<option value="{{ $clinic->id }}" @selected($managedUser?->clinic_id===$clinic->id)>{{ $clinic->name }}</option>@endforeach</select></label>
<label class="text-xs font-medium">{{ $managedUser ? 'Password Baru' : 'Password *' }}<input name="password" type="password" {{ $managedUser?'':'required' }} class="mt-1.5 w-full rounded-xl border p-2.5 text-sm"></label>
<label class="text-xs font-medium">Konfirmasi Password<input name="password_confirmation" type="password" {{ $managedUser?'':'required' }} class="mt-1.5 w-full rounded-xl border p-2.5 text-sm"></label>
@if($managedUser)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($managedUser->is_active)> User aktif</label>@endif
<div class="flex justify-end gap-2 sm:col-span-2"><button type="button" data-modal-close class="rounded-xl border px-4 py-2.5 text-sm">Batal</button><button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Simpan</button></div>
</div>
