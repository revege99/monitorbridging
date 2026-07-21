<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Aktivasi Instalasi</title>
    @fonts
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="setup-page min-h-screen bg-[#f4f7fb] text-slate-900">
<div class="min-h-screen lg:grid lg:grid-cols-[220px_minmax(0,1fr)]">
    <aside class="relative bg-[#071325] text-white">
        <div class="relative flex h-full flex-col px-[5px] py-5">
            <h1 class="px-2 text-xl font-semibold">Bridging Monitor</h1>
            <nav class="mt-6 flex-1 overflow-y-auto"><a href="{{ url('/') }}" class="flex rounded-xl px-2 py-2.5 text-sm text-slate-300">Dashboard</a>@include('partials.setup-sidebar-complete') @include('partials.setup-clinic-menu')</nav>
        </div>
    </aside>
    <main>
        <header class="app-page-header"><div class="flex items-center justify-between px-4 py-2.5"><div><h2 class="font-semibold">Aktivasi Instalasi</h2><p class="text-xs text-slate-500">Tetapkan instalasi ini untuk satu klinik</p></div>@include('partials.clinic-selector')</div></header>
        <div class="mx-auto max-w-6xl p-4"><section class="rounded-2xl border bg-white p-6 shadow-sm">
            @if($installation)
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5"><p class="text-xs font-semibold text-emerald-700">INSTALASI AKTIF</p><h3 class="mt-2 text-xl font-semibold">{{ $installation->clinic->name }}</h3><p class="mt-1 text-sm text-slate-600">{{ $installation->clinic->code }} · Diaktifkan {{ $installation->activated_at?->format('d-m-Y H:i') }}</p></div>
            @endif
            <form id="activation-form" method="POST" action="{{ route('installation.activate') }}">@csrf
                <label class="text-sm font-medium">Pilih Klinik
                    <select id="activation-clinic" name="clinic_id" required class="mt-2 w-full rounded-xl border border-slate-200 p-3">@foreach($clinics as $clinic)<option value="{{ $clinic->id }}" @selected($installation?->clinic_id===$clinic->id)>{{ $clinic->name }}</option>@endforeach</select>
                </label>
                <button id="activation-open" type="button" class="mt-4 h-[35px] rounded-xl bg-blue-600 px-5 text-xs font-semibold text-white transition hover:bg-blue-700">Aktifkan Klinik</button>
            </form>
        </section></div>
    </main>
</div>

<div id="activation-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="activation-modal-title">
    <div class="w-full max-w-md translate-y-2 scale-[0.98] overflow-hidden rounded-3xl border border-white/70 bg-white opacity-0 shadow-[0_28px_90px_rgba(15,23,42,0.32)] transition duration-200" data-confirm-card>
        <div class="bg-[linear-gradient(135deg,_#eff6ff_0%,_#ecfeff_100%)] px-6 pt-6 pb-5">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-200"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M3 12h18"/><circle cx="12" cy="12" r="9"/></svg></div>
                <div><p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Konfirmasi Aktivasi</p><h3 id="activation-modal-title" class="mt-1 text-lg font-semibold text-slate-900">Aktifkan instalasi ini?</h3><p class="mt-2 text-sm leading-6 text-slate-600">Seluruh service dan konfigurasi instalasi akan menggunakan klinik berikut.</p></div>
            </div>
        </div>
        <div class="px-6 py-5">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Klinik yang dipilih</p><p id="activation-clinic-name" class="mt-1 text-sm font-semibold text-slate-900">-</p></div>
            <div class="mt-5 flex justify-end gap-3"><button id="activation-cancel" type="button" class="inline-flex h-10 items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button><button id="activation-confirm" type="button" class="inline-flex h-10 items-center justify-center rounded-2xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Ya, Aktifkan</button></div>
        </div>
    </div>
</div>

@if(session('success') || $errors->any())
    @php($toastSuccess = session()->has('success'))
    <div id="flash-toast" class="pointer-events-none fixed right-4 bottom-4 z-[110] w-full max-w-sm translate-y-3 opacity-0 transition duration-300 sm:right-6 sm:bottom-6">
        <div class="pointer-events-auto overflow-hidden rounded-3xl border {{ $toastSuccess ? 'border-emerald-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#f0fdf4_100%)]' : 'border-rose-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#fff1f2_100%)]' }} shadow-[0_24px_80px_rgba(15,23,42,0.18)]">
            <div class="flex items-start gap-4 px-5 py-4"><div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $toastSuccess ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">@if($toastSuccess)<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.3 2.3 4.7-4.8"/></svg>@else<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4M12 16h.01"/><path d="M10.3 3.24 1.82 18a2 2 0 0 0 1.74 3h16.88a2 2 0 0 0 1.74-3L13.7 3.24a2 2 0 0 0-3.4 0Z"/></svg>@endif</div><div class="min-w-0 flex-1"><p class="text-sm font-semibold {{ $toastSuccess ? 'text-emerald-900' : 'text-rose-900' }}">{{ $toastSuccess ? 'Aktivasi Berhasil' : 'Aktivasi Gagal' }}</p><p class="mt-1 text-xs leading-5 {{ $toastSuccess ? 'text-emerald-800' : 'text-rose-800' }}">{{ session('success') ?? $errors->first() }}</p></div><button type="button" class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 hover:bg-white/70" data-flash-close aria-label="Tutup"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg></button></div>
            <div class="h-1.5 w-full {{ $toastSuccess ? 'bg-emerald-100' : 'bg-rose-100' }}"><div class="h-full origin-left {{ $toastSuccess ? 'bg-emerald-500' : 'bg-rose-500' }}" data-flash-progress></div></div>
        </div>
    </div>
@endif

<script>
document.querySelectorAll('[data-dropdown-toggle]').forEach(b=>{const p=b.parentElement.querySelector('[data-dropdown-panel]'),i=b.querySelector('[data-dropdown-icon]');b.addEventListener('click',()=>{const open=b.getAttribute('aria-expanded')==='true';b.setAttribute('aria-expanded',open?'false':'true');p?.classList.toggle('hidden',open);i?.classList.toggle('rotate-180',!open)})});
(()=>{const modal=document.getElementById('activation-modal'),card=modal.querySelector('[data-confirm-card]'),form=document.getElementById('activation-form'),select=document.getElementById('activation-clinic'),name=document.getElementById('activation-clinic-name'),open=document.getElementById('activation-open'),cancel=document.getElementById('activation-cancel'),confirm=document.getElementById('activation-confirm');const show=()=>{name.textContent=select.options[select.selectedIndex]?.text||'-';modal.classList.remove('hidden');modal.classList.add('flex');requestAnimationFrame(()=>card.classList.remove('translate-y-2','scale-[0.98]','opacity-0'));};const close=()=>{card.classList.add('translate-y-2','scale-[0.98]','opacity-0');setTimeout(()=>{modal.classList.add('hidden');modal.classList.remove('flex')},180)};open.addEventListener('click',show);cancel.addEventListener('click',close);modal.addEventListener('click',e=>{if(e.target===modal)close()});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!modal.classList.contains('hidden'))close()});confirm.addEventListener('click',()=>{confirm.disabled=true;confirm.textContent='Mengaktifkan...';form.submit()});})();
(()=>{const host=document.getElementById('flash-toast'),close=host?.querySelector('[data-flash-close]'),progress=host?.querySelector('[data-flash-progress]');if(!host||!close||!progress)return;requestAnimationFrame(()=>host.classList.remove('translate-y-3','opacity-0'));const dismiss=()=>{host.classList.add('translate-y-3','opacity-0');setTimeout(()=>host.remove(),280)};progress.animate([{transform:'scaleX(1)'},{transform:'scaleX(0)'}],{duration:4200,easing:'linear',fill:'forwards'});const timer=setTimeout(dismiss,4200);close.addEventListener('click',()=>{clearTimeout(timer);dismiss()})})();
</script>
<script src="{{ asset('js/sidebar-state.js') }}"></script>
</body></html>
