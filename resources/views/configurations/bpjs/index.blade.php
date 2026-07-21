<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konfigurasi BPJS - Bridging Monitor</title>
    @fonts
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bpjs-config-page setup-page min-h-screen bg-[#f4f7fb] text-slate-900 lg:h-dvh lg:overflow-hidden">
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
        <header class="app-page-header"><div class="flex items-center justify-between px-4 py-2.5"><div><h2 class="text-base font-semibold">Konfigurasi BPJS</h2><div class="mt-1 text-xs text-slate-500">Konfigurasi Bridging &gt; BPJS</div></div>@include('partials.clinic-selector')</div></header>
        <div class="flex-1 overflow-auto p-4">
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b px-5 py-4"><div><h3 class="font-semibold">Kredensial BPJS</h3><p class="mt-1 text-xs text-slate-500">Satu konfigurasi kredensial untuk setiap klinik.</p></div><button data-modal-open="bpjs-create" class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white">+ Tambah BPJS</button></div>
                <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="px-5 py-3">Klinik</th><th>Cons ID</th><th>Kode Aplikasi</th><th>Username</th><th>Status Key</th><th class="pr-5 text-right">Aksi</th></tr></thead><tbody>
                @forelse($configurations as $configuration)
                    <tr class="border-t"><td class="px-5 py-4 font-semibold">{{ $configuration->clinic->name }}</td><td>{{ $configuration->cons_id }}</td><td>{{ $configuration->kode_aplikasi }}</td><td>{{ $configuration->username }}</td><td><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Tersimpan</span></td><td class="pr-5 text-right"><button data-modal-open="bpjs-edit-{{ $configuration->id }}" class="rounded-lg border px-3 py-2 text-xs font-semibold text-blue-600">Edit</button> <form method="POST" action="{{ route('configurations.bpjs.destroy', $configuration) }}" class="inline" onsubmit="return confirm('Hapus konfigurasi BPJS ini?')">@csrf @method('DELETE')<button class="rounded-lg border px-3 py-2 text-xs font-semibold text-rose-600">Hapus</button></form></td></tr>
                @empty<tr><td colspan="6" class="p-12 text-center text-slate-500">Belum ada konfigurasi BPJS.</td></tr>@endforelse
                </tbody></table></div>
            </section>
            <x-setup-modal id="bpjs-create" title="Tambah Konfigurasi BPJS"><form method="POST" action="{{ route('configurations.bpjs.store') }}">@csrf @include('configurations.bpjs.form', ['configuration' => null])</form></x-setup-modal>
            @foreach($configurations as $configuration)<x-setup-modal id="bpjs-edit-{{ $configuration->id }}" title="Edit BPJS - {{ $configuration->clinic->name }}"><form method="POST" action="{{ route('configurations.bpjs.update', $configuration) }}">@csrf @method('PUT') @include('configurations.bpjs.form')</form></x-setup-modal>@endforeach
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
<script src="{{ asset('js/sidebar-state.js') }}"></script>
</body></html>
