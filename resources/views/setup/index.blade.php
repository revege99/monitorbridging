<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Klinik - Bridging Monitor</title>
    @fonts
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="setup-page min-h-screen bg-[#f4f7fb] text-slate-900 lg:h-dvh lg:overflow-hidden">
<div class="min-h-screen lg:grid lg:h-dvh lg:grid-cols-[220px_minmax(0,1fr)]">
    <aside class="relative overflow-hidden bg-[#071325] text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.22),_transparent_28%),linear-gradient(180deg,_#081426_0%,_#071220_100%)]"></div>
        <div class="relative flex h-full flex-col px-[5px] py-5">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-400/20 text-xl text-cyan-300">✚</div>
                <h1 class="text-xl font-semibold">Bridging Monitor</h1>
            </div>
            <nav class="mt-6 flex-1 overflow-y-auto">
                <a href="{{ url('/') }}" class="mb-2 flex items-center gap-3 rounded-xl px-2 py-2.5 text-sm text-slate-300 hover:bg-white/5"><span class="flex h-7 w-7 items-center justify-center">⌂</span><span>Dashboard</span></a>
                @include('partials.setup-sidebar-complete')
                @include('partials.setup-clinic-menu')
            </nav>
        </div>
    </aside>

    <main class="flex min-h-0 flex-col overflow-hidden">
        <header class="app-page-header">
            <div class="flex items-center justify-between px-4 py-2.5">
                <div>
                    <h2 class="text-base font-semibold">{{ ['clinics' => 'Profil Klinik', 'database' => 'Konfigurasi DB', 'users' => 'Manajemen User'][$tab] ?? 'Setup Klinik' }}</h2>
                    <div class="mt-1 flex items-center gap-2 text-xs text-slate-500"><span class="text-cyan-600">Setup Klinik</span><span>&gt;</span><span>{{ ['clinics' => 'Profil Klinik', 'database' => 'Konfigurasi DB', 'users' => 'Manajemen User'][$tab] ?? '' }}</span></div>
                </div>
                @include('partials.clinic-selector')
            </div>
        </header>

        <div class="flex-1 overflow-auto p-4">
            @if($tab === 'clinics')
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div><h3 class="font-semibold">Daftar Klinik</h3><p class="mt-1 text-xs text-slate-500">Kelola profil seluruh klinik yang terhubung.</p></div>
                        <button type="button" data-modal-open="clinic-create" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">+ Tambah Klinik</button>
                    </div>
                    <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="px-5 py-3">Klinik</th><th>Kode</th><th>Kontak</th><th>Lokasi</th><th>Status</th><th>Database</th><th class="pr-5 text-right">Aksi</th></tr></thead><tbody>
                    @forelse($clinics as $clinic)<tr class="border-t border-slate-100"><td class="px-5 py-4"><div class="font-semibold">{{ $clinic->name }}</div><div class="text-xs text-slate-500">{{ $clinic->legal_name ?: '-' }}</div></td><td>{{ $clinic->code }}</td><td><div>{{ $clinic->phone ?: '-' }}</div><div class="text-xs text-slate-500">{{ $clinic->email ?: '-' }}</div></td><td>{{ collect([$clinic->city,$clinic->province])->filter()->join(', ') ?: '-' }}</td><td><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $clinic->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $clinic->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>{{ $clinic->database?->database_name ?? 'Belum dikonfigurasi' }}</td><td class="pr-5"><div class="flex justify-end gap-2"><button type="button" data-modal-open="clinic-edit-{{ $clinic->id }}" title="Edit klinik" aria-label="Edit klinik" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-600 transition hover:border-blue-300 hover:bg-blue-100"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button><form method="POST" action="{{ route('setup.clinics.destroy',$clinic) }}" onsubmit="return confirm('Hapus klinik {{ addslashes($clinic->name) }}? Data konfigurasi database klinik juga akan dihapus.')">@csrf @method('DELETE')<button type="submit" title="Hapus klinik" aria-label="Hapus klinik" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:border-rose-300 hover:bg-rose-100"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg></button></form></div></td></tr>@empty<tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">Belum ada klinik.</td></tr>@endforelse
                    </tbody></table></div>
                </section>

                <x-setup-modal id="clinic-create" title="Tambah Klinik"><form method="POST" action="{{ route('setup.clinics.store') }}">@csrf @include('setup.partials.clinic-form', ['clinic' => null])</form></x-setup-modal>
                @foreach($clinics as $clinic)<x-setup-modal id="clinic-edit-{{ $clinic->id }}" title="Edit Profil Klinik"><form method="POST" action="{{ route('setup.clinics.update',$clinic) }}">@csrf @method('PUT') @include('setup.partials.clinic-form', ['clinic' => $clinic])</form></x-setup-modal>@endforeach

            @elseif($tab === 'database')
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b px-5 py-4"><div><h3 class="font-semibold">Konfigurasi Database</h3><p class="mt-1 text-xs text-slate-500">Satu koneksi SIMRS untuk setiap klinik.</p></div><button data-modal-open="database-create" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">+ Tambah Konfigurasi</button></div>
                    <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="px-5 py-3">Klinik</th><th>Nama Server</th><th>Host</th><th>Database</th><th>Driver</th><th>Collation</th><th class="pr-5 text-right">Aksi</th></tr></thead><tbody>
                    @forelse($clinics as $clinic)<tr class="border-t"><td class="px-5 py-4 font-semibold">{{ $clinic->name }}</td>@if($clinic->database)<td>{{ $clinic->database->connection_name }}</td><td>{{ $clinic->database->host }}:{{ $clinic->database->port }}</td><td>{{ $clinic->database->database_name }}</td><td>MariaDB</td><td>{{ $clinic->database->collation }}</td><td class="pr-5 text-right"><form method="POST" action="{{ route('setup.database.test') }}" class="inline">@csrf<input type="hidden" name="clinic_id" value="{{ $clinic->id }}"><button class="rounded-lg border px-3 py-2 text-xs font-semibold text-emerald-700">Tes</button></form> <button data-modal-open="database-edit-{{ $clinic->id }}" class="rounded-lg border px-3 py-2 text-xs font-semibold text-blue-600">Edit</button></td>@else<td colspan="5" class="text-slate-400">Belum dikonfigurasi</td><td class="pr-5 text-right"><button data-modal-open="database-edit-{{ $clinic->id }}" class="rounded-lg border px-3 py-2 text-xs font-semibold text-blue-600">Konfigurasi</button></td>@endif</tr>@empty<tr><td colspan="7" class="p-12 text-center text-slate-500">Tambahkan profil klinik terlebih dahulu.</td></tr>@endforelse
                    </tbody></table></div>
                </section>
                <x-setup-modal id="database-create" title="Tambah Konfigurasi Database"><form method="POST" action="{{ route('setup.database.save') }}">@csrf @include('setup.partials.database-form', ['selectedClinic' => null])</form></x-setup-modal>
                @foreach($clinics as $clinic)<x-setup-modal id="database-edit-{{ $clinic->id }}" title="Konfigurasi {{ $clinic->name }}"><form method="POST" action="{{ route('setup.database.save') }}">@csrf @include('setup.partials.database-form', ['selectedClinic' => $clinic])</form></x-setup-modal>@endforeach

            @else
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b px-5 py-4"><div><h3 class="font-semibold">Manajemen User</h3><p class="mt-1 text-xs text-slate-500">Atur akses superadmin dan admin klinik.</p></div><button data-modal-open="user-create" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">+ Tambah User</button></div>
                    <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="px-5 py-3">User</th><th>Role</th><th>Klinik</th><th>Status</th><th class="pr-5 text-right">Aksi</th></tr></thead><tbody>
                    @forelse($users as $managedUser)<tr class="border-t"><td class="px-5 py-4"><div class="font-semibold">{{ $managedUser->name }}</div><div class="text-xs text-slate-500">{{ $managedUser->email }}</div></td><td><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ ucfirst($managedUser->role) }}</span></td><td>{{ $managedUser->clinic?->name ?? 'Semua Klinik' }}</td><td>{{ $managedUser->is_active ? 'Aktif' : 'Nonaktif' }}</td><td class="pr-5 text-right"><button data-modal-open="user-edit-{{ $managedUser->id }}" class="rounded-lg border px-3 py-2 text-xs font-semibold text-blue-600">Edit</button></td></tr>@empty<tr><td colspan="5" class="p-12 text-center text-slate-500">Belum ada user.</td></tr>@endforelse
                    </tbody></table></div>
                </section>
                <x-setup-modal id="user-create" title="Tambah User"><form method="POST" action="{{ route('setup.users.store') }}">@csrf @include('setup.partials.user-form', ['managedUser' => null])</form></x-setup-modal>
                @foreach($users as $managedUser)<x-setup-modal id="user-edit-{{ $managedUser->id }}" title="Edit User"><form method="POST" action="{{ route('setup.users.update',$managedUser) }}">@csrf @method('PUT') @include('setup.partials.user-form', ['managedUser' => $managedUser])</form>@if(!$managedUser->is(auth()->user()))<form method="POST" action="{{ route('setup.users.destroy',$managedUser) }}" onsubmit="return confirm('Hapus user ini?')" class="mt-3">@csrf @method('DELETE')<button class="text-xs font-semibold text-rose-600">Hapus user</button></form>@endif</x-setup-modal>@endforeach
            @endif
        </div>
    </main>
</div>
@if(session('success') || $errors->any())
    @php($toastSuccess = session()->has('success'))
    <div id="flash-toast" class="pointer-events-none fixed right-4 bottom-4 z-[100] w-full max-w-sm translate-y-3 opacity-0 transition duration-300 sm:right-6 sm:bottom-6">
        <div class="pointer-events-auto overflow-hidden rounded-3xl border {{ $toastSuccess ? 'border-emerald-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#f0fdf4_100%)]' : 'border-rose-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#fff1f2_100%)]' }} shadow-[0_24px_80px_rgba(15,23,42,0.18)]" data-flash-toast>
            <div class="flex items-start gap-4 px-5 py-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $toastSuccess ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                    @if($toastSuccess)<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.3 2.3 4.7-4.8"/></svg>@else<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 8v4M12 16h.01"/><path d="M10.3 3.24 1.82 18a2 2 0 0 0 1.74 3h16.88a2 2 0 0 0 1.74-3L13.7 3.24a2 2 0 0 0-3.4 0Z"/></svg>@endif
                </div>
                <div class="min-w-0 flex-1"><p class="text-sm font-semibold {{ $toastSuccess ? 'text-emerald-900' : 'text-rose-900' }}">{{ $toastSuccess ? 'Berhasil Disimpan' : 'Gagal Menyimpan' }}</p><p class="mt-1 text-xs leading-5 {{ $toastSuccess ? 'text-emerald-800' : 'text-rose-800' }}">{{ session('success') ?? $errors->first() }}</p></div>
                <button type="button" class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 transition hover:bg-white/70 hover:text-slate-600" data-flash-close aria-label="Tutup notifikasi"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
            </div>
            <div class="h-1.5 w-full {{ $toastSuccess ? 'bg-emerald-100' : 'bg-rose-100' }}"><div class="h-full origin-left {{ $toastSuccess ? 'bg-emerald-500' : 'bg-rose-500' }}" data-flash-progress></div></div>
        </div>
    </div>
@endif
<script>
document.querySelectorAll('[data-modal-open]').forEach(button => button.addEventListener('click', () => document.getElementById(button.dataset.modalOpen)?.showModal()));
document.querySelectorAll('[data-modal-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog')?.close()));
document.querySelectorAll('dialog').forEach(dialog => dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); }));
document.querySelectorAll('[data-dropdown-toggle]').forEach(button => { const panel=button.parentElement.querySelector('[data-dropdown-panel]'); const icon=button.querySelector('[data-dropdown-icon]'); button.addEventListener('click',()=>{ const open=button.getAttribute('aria-expanded')==='true'; button.setAttribute('aria-expanded',open?'false':'true'); panel.classList.toggle('hidden',open); icon.classList.toggle('rotate-180',!open); }); });
(() => {
    const host = document.getElementById('flash-toast');
    const closeButton = host?.querySelector('[data-flash-close]');
    const progress = host?.querySelector('[data-flash-progress]');
    if (!host || !closeButton || !progress) return;
    requestAnimationFrame(() => host.classList.remove('translate-y-3', 'opacity-0'));
    const closeToast = () => { host.classList.add('translate-y-3', 'opacity-0'); setTimeout(() => host.remove(), 280); };
    progress.animate([{transform:'scaleX(1)'},{transform:'scaleX(0)'}], {duration:4200,easing:'linear',fill:'forwards'});
    const timer = setTimeout(closeToast, 4200);
    closeButton.addEventListener('click', () => { clearTimeout(timer); closeToast(); });
})();
</script>
<script src="{{ asset('js/sidebar-state.js') }}"></script>
</body></html>
