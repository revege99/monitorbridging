<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Bridging Monitor</title>
    @fonts
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="setup-page min-h-screen bg-[#f3f7fb] text-slate-900 lg:h-dvh lg:overflow-hidden">
<div class="min-h-screen lg:grid lg:h-dvh lg:grid-cols-[220px_minmax(0,1fr)]">
    <aside class="relative overflow-hidden bg-[#071325] text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(34,211,238,.14),_transparent_26%),linear-gradient(180deg,_#081426_0%,_#071220_100%)]"></div>
        <div class="relative flex h-full min-h-0 flex-col px-[5px] py-5">
            <div class="flex items-center gap-3 px-2">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-400/15 text-cyan-300">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12h4l2-5 4 10 2-5h6"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
                </div>
                <div><h1 class="text-lg font-semibold leading-tight">Bridging<br>Monitor</h1></div>
            </div>
            <nav class="mt-6 flex-1 overflow-y-auto pr-0">
                <a href="{{ route('dashboard') }}" class="flex h-10 w-full items-center gap-2 rounded-2xl px-0 text-left text-white transition hover:bg-white/5">
                    <span class="flex h-10 w-11 shrink-0 items-center justify-center text-blue-300"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 10 9-7 9 7"/><path d="M5 9v11h14V9"/><path d="M9 20v-6h6v6"/></svg></span>
                    <span class="min-w-0 flex-1 text-sm font-semibold">Dashboard</span>
                </a>
                @include('partials.setup-sidebar-complete')
                @include('partials.setup-clinic-menu')
            </nav>
        </div>
    </aside>

    <main class="flex min-h-0 min-w-0 flex-col overflow-hidden">
        <header class="app-page-header">
            <div class="flex items-center justify-between gap-4 px-4 py-2.5 lg:px-5">
                <div><h2 class="text-base font-semibold">Dashboard</h2><p class="mt-0.5 text-xs text-slate-500">Ringkasan operasional {{ $activeClinic?->name ?? 'klinik' }}</p></div>
                @include('partials.clinic-selector')
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-5">
            @if($dbError)
                <div class="mb-4 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01"/></svg>{{ $dbError }}
                </div>
            @endif

            <section class="relative mb-4 overflow-hidden rounded-3xl bg-gradient-to-r from-[#08223a] via-[#0a3150] to-[#07546a] p-5 text-white shadow-[0_18px_45px_rgba(15,23,42,.14)] lg:p-6">
                <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full border-[38px] border-cyan-300/8"></div>
                <div class="absolute bottom-0 right-1/4 h-24 w-24 rounded-full bg-blue-400/10 blur-2xl"></div>
                <div class="relative flex flex-col justify-between gap-5 md:flex-row md:items-center">
                    <div>
                        <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 py-1 text-xs text-cyan-100"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Data pelayanan hari ini</div>
                        <h3 class="text-2xl font-semibold tracking-tight">Selamat datang, {{ auth()->user()->name }}</h3>
                        <p class="mt-1.5 max-w-2xl text-sm text-slate-300">Pantau proses bridging, antrean BPJS, dan aktivitas pelayanan klinik dari satu halaman.</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <a href="{{ route('monitoring.bridging-pelayanan') }}" class="rounded-xl bg-white px-4 py-2.5 text-xs font-semibold text-slate-800 shadow-sm transition hover:bg-cyan-50">Lihat Bridging</a>
                        <a href="{{ route('service-monitor.index') }}" class="rounded-xl border border-white/15 bg-white/8 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-white/15">Monitor Service</a>
                    </div>
                </div>
            </section>

            @php
                $cards = [
                    ['Kunjungan Hari Ini', $metrics['registrations'], 'Seluruh penjamin aktif', 'bg-blue-50 text-blue-600', 'M4 19V9m6 10V5m6 14v-7m4 7H2'],
                    ['Pasien BPJS', $metrics['bpjs'], 'Registrasi penjamin BPJS', 'bg-cyan-50 text-cyan-600', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8m11 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'],
                    ['Bridging Lengkap', $metrics['bridged'], 'Pendaftaran & kunjungan', 'bg-emerald-50 text-emerald-600', 'm5 12 4 4L19 6'],
                    ['Belum Bridging', $metrics['not_bridged'], 'Perlu ditindaklanjuti', 'bg-rose-50 text-rose-600', 'M12 8v5m0 4h.01M10.3 3.7 2-1.2 2 1.2 7 12.1A2 2 0 0 1 19.6 19H4.4a2 2 0 0 1-1.7-3.2l7.6-12.1Z'],
                ];
            @endphp
            <section class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($cards as [$label,$value,$note,$iconClass,$path])
                    <article class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div><p class="text-xs font-medium text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ number_format($value) }}</p></div>
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $iconClass }}"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="{{ $path }}"/></svg></span>
                        </div>
                        <p class="mt-3 text-[11px] text-slate-400">{{ $note }}</p>
                    </article>
                @endforeach
            </section>

            <section class="grid gap-4 xl:grid-cols-[1.45fr_.85fr]">
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h3 class="text-sm font-semibold">Registrasi Terbaru</h3><p class="mt-0.5 text-xs text-slate-400">Pasien yang didaftarkan hari ini</p></div><a href="{{ route('monitoring.statistik-pasien') }}" class="text-xs font-semibold text-blue-600">Lihat statistik</a></div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 text-slate-500"><tr><th class="px-5 py-3 font-medium">Pasien</th><th class="py-3 font-medium">Poliklinik</th><th class="py-3 font-medium">Penjamin</th><th class="py-3 font-medium">Jam</th><th class="pr-5 text-right font-medium">Status</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                            @forelse($recentPatients as $patient)
                                <tr><td class="px-5 py-3"><p class="max-w-48 truncate font-semibold text-slate-700">{{ $patient->nm_pasien ?: '-' }}</p><p class="mt-0.5 text-[10px] text-slate-400">{{ $patient->no_rawat }}</p></td><td class="py-3 text-slate-600">{{ $patient->nm_poli ?: '-' }}</td><td class="py-3"><span class="rounded-full {{ strtoupper($patient->kd_pj)==='BPJ' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }} px-2 py-1 text-[10px] font-semibold">{{ $patient->kd_pj }}</span></td><td class="py-3 text-slate-500">{{ substr((string)$patient->jam_reg,0,5) }}</td><td class="pr-5 text-right"><span class="text-[10px] font-semibold {{ $patient->stts === 'Batal' ? 'text-rose-600' : 'text-emerald-600' }}">{{ $patient->stts }}</span></td></tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">Belum ada registrasi hari ini.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-4">
                    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between"><div><p class="text-xs text-slate-500">Service Antrean FKTP</p><p class="mt-1 text-lg font-semibold">{{ $serviceOnline ? 'Online' : 'Offline' }}</p></div><span class="flex items-center gap-1.5 rounded-full {{ $serviceOnline ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-[10px] font-semibold"><span class="h-1.5 w-1.5 rounded-full {{ $serviceOnline ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>{{ $serviceOnline ? 'AKTIF' : 'TIDAK AKTIF' }}</span></div>
                        <div class="mt-4 grid grid-cols-3 gap-2 border-t border-slate-100 pt-4 text-center"><div><p class="text-lg font-semibold">{{ $serviceRun?->processed ?? 0 }}</p><p class="text-[10px] text-slate-400">Diproses</p></div><div><p class="text-lg font-semibold text-emerald-600">{{ $serviceRun?->succeeded ?? 0 }}</p><p class="text-[10px] text-slate-400">Berhasil</p></div><div><p class="text-lg font-semibold text-rose-600">{{ $serviceRun?->failed ?? 0 }}</p><p class="text-[10px] text-slate-400">Gagal</p></div></div>
                    </article>

                    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <div class="mb-4 flex items-center justify-between"><div><h3 class="text-sm font-semibold">Kunjungan per Poli</h3><p class="mt-0.5 text-xs text-slate-400">Hari ini</p></div></div>
                        <div class="space-y-3">
                        @php($maxPoli = max(1, (int)($poliRows->max('total') ?? 1)))
                        @forelse($poliRows as $poli)
                            <div><div class="mb-1.5 flex justify-between text-xs"><span class="max-w-52 truncate text-slate-600">{{ $poli->nm_poli }}</span><strong>{{ $poli->total }}</strong></div><div class="h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-blue-500" style="width:{{ max(5, round(($poli->total/$maxPoli)*100)) }}%"></div></div></div>
                        @empty
                            <p class="py-6 text-center text-xs text-slate-400">Belum ada data poli.</p>
                        @endforelse
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </main>
</div>

<script>
document.querySelectorAll('[data-dropdown-toggle]').forEach(button => {
    const panel = button.parentElement?.querySelector('[data-dropdown-panel]');
    const icon = button.querySelector('[data-dropdown-icon]');
    button.addEventListener('click', () => {
        const open = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', open ? 'false' : 'true');
        panel?.classList.toggle('hidden', open);
        icon?.classList.toggle('rotate-180', !open);
    });
});
</script>
<script src="{{ asset('js/sidebar-state.js') }}"></script>
</body>
</html>
