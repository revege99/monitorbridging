<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konfigurasi BPJS - Bridging Monitor</title>
    @fonts
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="pcare-page bpjs-credentials-page setup-page min-h-screen bg-[#f4f7fb] text-slate-900 lg:h-dvh lg:overflow-hidden">
<div class="min-h-screen lg:grid lg:h-dvh lg:grid-cols-[220px_minmax(0,1fr)]">
    <aside class="relative overflow-hidden bg-[#071325] text-white">
        <div class="absolute inset-0 bg-[linear-gradient(180deg,_#081426_0%,_#071220_100%)]"></div>
        <div class="relative flex h-full flex-col px-[5px] py-5">
            <div class="flex items-center gap-4"><div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-400/20 text-cyan-300">+</div><h1 class="text-xl font-semibold">Bridging Monitor</h1></div>
            <nav class="mt-6 flex-1 overflow-y-auto">
                <a href="{{ url('/') }}" class="mb-2 flex items-center gap-3 rounded-xl px-2 py-2.5 text-sm text-slate-300 hover:bg-white/5"><span class="flex h-7 w-7 items-center justify-center">⌂</span><span>Dashboard</span></a>
                @include('partials.setup-sidebar-complete')
                @include('partials.setup-clinic-menu')
            </nav>
        </div>
    </aside>
    <main class="flex min-h-0 flex-col overflow-hidden">
        <header class="app-page-header"><div class="flex items-center justify-between px-4 py-2.5"><div><h2 class="text-base font-semibold">Konfigurasi BPJS</h2><div class="mt-1 text-xs text-slate-500">Konfigurasi &gt; BPJS</div></div>@include('partials.clinic-selector')</div></header>
        <div class="flex-1 overflow-auto p-4">
            <section class="pcare-card" aria-labelledby="pcare-heading">
                <div class="pcare-toolbar">
                    <div class="pcare-heading"><span class="pcare-emblem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Z"/><circle cx="13" cy="10" r="2"/><path d="m11.5 11.5-4 4M8 15l1 1"/></svg></span><div><h3 id="pcare-heading">Kredensial BPJS</h3><p>Kelola kredensial integrasi BPJS untuk setiap klinik.</p></div></div>
                    <div class="pcare-tools"><label class="pcare-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10" cy="10" r="7"/><path d="m15 15 5 5"/></svg><input id="pcare-search" type="search" placeholder="Cari konfigurasi..." aria-label="Cari klinik, Cons ID, kode aplikasi, atau username"></label><button data-modal-open="pcare-create" class="pcare-add"><span aria-hidden="true">&#43;</span> Tambah Kredensial</button></div>
                </div>
                <div class="pcare-table-wrap"><table class="pcare-table"><thead><tr><th>Klinik</th><th>Cons ID</th><th>Username</th><th>Kode Aplikasi</th><th>Aksi</th></tr></thead><tbody>
                @foreach($configurations as $configuration)
                    <tr data-pcare-row data-search="{{ mb_strtolower($configuration->clinic->name.' '.$configuration->cons_id.' '.$configuration->username.' '.$configuration->kode_aplikasi) }}">
                        <td>{{ $configuration->clinic->name }}</td>
                        @foreach(['cons_id' => 'Cons ID', 'username' => 'Username'] as $field => $label)
                        <td><div class="pcare-credential" data-value="{{ $configuration->{$field} }}"><span>{{ $configuration->{$field} }}</span><button type="button" data-copy aria-label="Salin {{ $label }}" title="Salin {{ $label }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="7" width="11" height="14" rx="2"/><path d="M9 7V3h11v14h-5"/></svg></button></div></td>
                        @endforeach
                        <td>{{ $configuration->kode_aplikasi }}</td>
                        <td><div class="pcare-actions"><button type="button" class="pcare-more" popovertarget="pcare-actions-{{ $configuration->id }}" aria-label="Aksi konfigurasi {{ $configuration->id }}"><svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg></button><div popover id="pcare-actions-{{ $configuration->id }}" class="pcare-action-menu"><button type="button" data-modal-open="pcare-edit-{{ $configuration->id }}" aria-label="Edit konfigurasi {{ $configuration->id }}" title="Edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m14 5 5 5M4 20l5-1L21 7l-5-5L4 14Z"/></svg><span>Edit</span></button><form method="POST" action="{{ route('configurations.bpjs.destroy', $configuration) }}" onsubmit="return confirm('Hapus Konfigurasi BPJS ini?')">@csrf @method('DELETE')<button class="pcare-delete" aria-label="Hapus konfigurasi {{ $configuration->id }}" title="Hapus"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg><span>Hapus</span></button></form></div></div></td>
                    </tr>
                @endforeach
                    <tr id="pcare-empty" @if($configurations->isNotEmpty()) hidden @endif><td colspan="5">{{ $configurations->isEmpty() ? 'Belum ada konfigurasi BPJS. Tambahkan kredensial untuk memulai.' : 'Tidak ada konfigurasi yang sesuai pencarian.' }}</td></tr>
                </tbody></table></div>
                <div class="pcare-footer"><div><p class="pcare-note"><span aria-hidden="true">&#9432;</span> Kelola dan perbarui kredensial layanan BPJS.</p><p id="pcare-count" aria-live="polite"></p><p id="pcare-feedback" role="status"></p></div><div class="pcare-pagination"><select id="pcare-size" aria-label="Jumlah konfigurasi per halaman"><option value="10">10 / halaman</option><option value="25">25 / halaman</option><option value="50">50 / halaman</option></select><button id="pcare-prev" aria-label="Halaman sebelumnya">&#8249;</button><span id="pcare-page-number">1</span><button id="pcare-next" aria-label="Halaman berikutnya">&#8250;</button></div></div>
            </section>
            <x-pcare-modal subtitle="Kelola akses integrasi BPJS untuk setiap klinik." id="pcare-create" title="Tambah Konfigurasi BPJS"><form method="POST" action="{{ route('configurations.bpjs.store') }}">@csrf @include('configurations.bpjs.form', ['configuration' => null])</form></x-pcare-modal>
            @foreach($configurations as $configuration)<x-pcare-modal subtitle="Kelola akses integrasi BPJS untuk setiap klinik." id="pcare-edit-{{ $configuration->id }}" title="Edit Konfigurasi BPJS - {{ $configuration->id }}"><form method="POST" action="{{ route('configurations.bpjs.update', $configuration) }}">@csrf @method('PUT') @include('configurations.bpjs.form')</form></x-pcare-modal>@endforeach
        </div>
    </main>
</div>
@if(session('success') || $errors->any())
    @php($toastSuccess = session()->has('success'))
    <div id="flash-toast" class="pointer-events-none fixed right-4 bottom-4 z-[110] w-full max-w-sm translate-y-3 opacity-0 transition duration-300 sm:right-6 sm:bottom-6">
        <div class="pointer-events-auto overflow-hidden rounded-3xl border {{ $toastSuccess ? 'border-emerald-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#f0fdf4_100%)]' : 'border-rose-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#fff1f2_100%)]' }} shadow-[0_24px_80px_rgba(15,23,42,0.18)]">
            <div class="flex items-start gap-4 px-5 py-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $toastSuccess ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                    @if($toastSuccess)<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.3 2.3 4.7-4.8"/></svg>@else<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4M12 16h.01"/><path d="M10.3 3.24 1.82 18a2 2 0 0 0 1.74 3h16.88a2 2 0 0 0 1.74-3L13.7 3.24a2 2 0 0 0-3.4 0Z"/></svg>@endif
                </div>
                <div class="min-w-0 flex-1"><p class="text-sm font-semibold {{ $toastSuccess ? 'text-emerald-900' : 'text-rose-900' }}">{{ $toastSuccess ? 'Berhasil Disimpan' : 'Gagal Menyimpan' }}</p><p class="mt-1 text-xs leading-5 {{ $toastSuccess ? 'text-emerald-800' : 'text-rose-800' }}">{{ session('success') ?? $errors->first() }}</p></div>
                <button type="button" class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 hover:bg-white/70" data-flash-close aria-label="Tutup"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
            </div>
            <div class="h-1.5 w-full {{ $toastSuccess ? 'bg-emerald-100' : 'bg-rose-100' }}"><div class="h-full origin-left {{ $toastSuccess ? 'bg-emerald-500' : 'bg-rose-500' }}" data-flash-progress></div></div>
        </div>
    </div>
@endif
<script>document.querySelectorAll('[data-modal-open]').forEach(b=>b.addEventListener('click',()=>document.getElementById(b.dataset.modalOpen)?.showModal()));document.querySelectorAll('[data-modal-close]').forEach(b=>b.addEventListener('click',()=>b.closest('dialog')?.close()));document.querySelectorAll('[data-dropdown-toggle]').forEach(b=>{const p=b.parentElement.querySelector('[data-dropdown-panel]'),i=b.querySelector('[data-dropdown-icon]');b.addEventListener('click',()=>{const o=b.getAttribute('aria-expanded')==='true';b.setAttribute('aria-expanded',o?'false':'true');p?.classList.toggle('hidden',o);i?.classList.toggle('rotate-180',!o);});});</script>
<script>(()=>{const host=document.getElementById('flash-toast'),close=host?.querySelector('[data-flash-close]'),progress=host?.querySelector('[data-flash-progress]');if(!host||!close||!progress)return;requestAnimationFrame(()=>host.classList.remove('translate-y-3','opacity-0'));const dismiss=()=>{host.classList.add('translate-y-3','opacity-0');setTimeout(()=>host.remove(),280)};progress.animate([{transform:'scaleX(1)'},{transform:'scaleX(0)'}],{duration:4200,easing:'linear',fill:'forwards'});const timer=setTimeout(dismiss,4200);close.addEventListener('click',()=>{clearTimeout(timer);dismiss()})})();</script>
<script src="{{ asset('js/pcare-config.js') }}"></script>
<script src="{{ asset('js/sidebar-state.js') }}"></script>
</body></html>
