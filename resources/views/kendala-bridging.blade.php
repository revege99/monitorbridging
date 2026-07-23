<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Monitoring Bridging') }}</title>

        @fonts
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    </head>
    <body class="min-h-screen bg-[#f4f7fb] text-slate-900">
        <div class="min-h-screen lg:grid lg:grid-cols-[220px_minmax(0,1fr)]">
            <aside class="relative overflow-hidden bg-[#071325] text-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.22),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.12),_transparent_20%),linear-gradient(180deg,_#081426_0%,_#09182b_55%,_#071220_100%)]"></div>
                <div class="absolute inset-y-0 right-0 w-px bg-white/8"></div>

                <div class="relative flex h-full flex-col px-[5px] py-5">
                    <div class="flex items-start gap-3 px-0">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400/25 to-blue-500/15 shadow-[0_12px_30px_rgba(37,99,235,0.25)] ring-1 ring-white/10">
                                <svg class="h-7 w-7 text-cyan-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 14h4l2.2-5.2a.7.7 0 0 1 1.31.05L12 18l2.6-8.1a.7.7 0 0 1 1.33-.03L17.9 14H22" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl font-semibold leading-none tracking-tight">Bridging Monitor</h1>
                            </div>
                        </div>
                    </div>

                    <nav class="mt-6 flex-1 overflow-y-auto pr-0">
                        <div class="space-y-0">
                            <div class="-ml-[5px] mb-2">
                                <a href="{{ url('/') }}" class="relative flex items-center gap-2 overflow-hidden rounded-xl bg-gradient-to-r from-blue-600/20 to-blue-500/10 px-2 py-2 text-sm font-semibold text-white ring-1 ring-blue-400/15">
                                    <span class="absolute inset-y-0 left-0 w-1 rounded-r-full bg-cyan-400"></span>
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-500/18 text-blue-200">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 3.2 3 10v1.3h2V20h6v-5h2v5h6v-8.7h2V10z" />
                                        </svg>
                                    </span>
                                    <span>Dashboard</span>
                                </a>
                            </div>
                            <section>
                                <div class="rounded-2xl px-0">
                                    <button type="button" class="flex w-full cursor-pointer items-center gap-2 rounded-2xl px-0 py-1.5 text-left text-white" data-dropdown-toggle aria-expanded="true">
                                        <span class="flex h-11 w-11 items-center justify-center text-violet-200">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 4h18v12H3z" />
                                                <path d="M8 20h8" />
                                                <path d="M12 16v4" />
                                            </svg>
                                        </span>
                                        <span class="min-w-0 flex-1 text-sm font-semibold">Monitor BPJS</span>
                                        <div class="ml-auto flex shrink-0 items-center gap-2">
                                            <div class="h-px w-10 bg-white/10"></div>
                                            <svg class="h-5 w-5 text-slate-400 transition duration-200" data-dropdown-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </div>
                                    </button>
                                    <div class="mt-1 space-y-1 pl-[7px]" data-dropdown-panel>
                                        <a href="{{ route('monitoring.statistik-pasien') }}" class="flex items-center gap-2.5 rounded-xl px-2 py-2.5 text-sm text-slate-300 hover:bg-white/5">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                            <svg class="h-5 w-5 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                                                <circle cx="9.5" cy="7" r="3" />
                                                <path d="M20 21v-2a4 4 0 0 0-3-3.87" />
                                            </svg>
                                            <span>Statistik Pasien</span>
                                        </a>
                                        <a href="{{ route('monitoring.bridging-pelayanan') }}" class="flex items-center gap-2.5 rounded-xl px-2 py-2.5 text-sm text-slate-300 hover:bg-white/5">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                            <svg class="h-5 w-5 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m22 2-7 20-4-9-9-4Z" />
                                                <path d="M22 2 11 13" />
                                            </svg>
                                            <span>Bridging Pasien</span>
                                        </a>
                                        <a href="{{ route('monitoring.timeline-pelayanan') }}" class="flex items-center gap-2.5 rounded-xl px-2 py-2.5 text-sm text-slate-300 hover:bg-white/5">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                            <svg class="h-5 w-5 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M8 6h13" />
                                                <path d="M8 12h13" />
                                                <path d="M8 18h13" />
                                                <path d="M3 6h.01" />
                                                <path d="M3 12h.01" />
                                                <path d="M3 18h.01" />
                                            </svg>
                                            <span>Timeline Pelayanan</span>
                                        </a>
                                        <a href="{{ route('monitoring.bpjs-tidak-bridging') }}" class="flex items-center gap-2.5 rounded-xl px-2 py-2.5 text-sm text-slate-300 hover:bg-white/5">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                            <svg class="h-5 w-5 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M5 6h14" />
                                                <path d="M7 6v12" />
                                                <path d="M17 6v12" />
                                                <path d="M5 10h14" />
                                                <path d="M5 14h14" />
                                                <path d="M5 18h14" />
                                            </svg>
                                            <span>Rujukan Non Spesialistik</span>
                                        </a>
                                        <a href="{{ route('monitoring.kendala-bridging') }}" class="flex items-center gap-2.5 rounded-xl bg-cyan-400/8 px-2 py-2.5 text-sm text-white ring-1 ring-cyan-400/10">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                            <svg class="h-5 w-5 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m10.29 3.86-1.82 3.68-4.05.59 2.93 2.86-.69 4.04L10.29 13l3.63 1.91-.69-4.04 2.93-2.86-4.05-.59Z" />
                                                <path d="M12 17h.01" />
                                            </svg>
                                            <span>Kendala Bridging</span>
                                        </a>
                                    </div>
                                </div>
                            </section>

                            <section>
                                <div class="rounded-2xl px-0">
                                    <button type="button" class="flex w-full cursor-pointer items-center gap-2.5 rounded-2xl px-0 py-1.5 text-left text-white" data-dropdown-toggle aria-expanded="true">
                                        <span class="flex h-11 w-11 items-center justify-center text-emerald-200">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M7 12h10" />
                                                <path d="M12 7v10" />
                                                <circle cx="12" cy="12" r="9" />
                                            </svg>
                                        </span>
                                        <span class="ml-1 min-w-0 flex-1 whitespace-nowrap text-sm font-semibold">Satu Sehat</span>
                                        <svg class="ml-2 h-5 w-5 shrink-0 text-slate-400 transition duration-200" data-dropdown-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <path d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                    <div class="mt-1 space-y-1 pl-[7px] text-sm text-slate-300" data-dropdown-panel>
                                        @foreach (['Encounter', 'Condition', 'Observation', 'Procedure', 'Clinical Impression', 'Medication Request', 'Medication Dispense', 'Medication Statement', 'Care Plan'] as $item)
                                            <a href="{{ $item === 'Encounter' ? route('satu-sehat.encounter') : ($item === 'Condition' ? route('satu-sehat.condition') : ($item === 'Observation' ? route('satu-sehat.observation') : ($item === 'Procedure' ? route('satu-sehat.procedure') : ($item === 'Clinical Impression' ? route('satu-sehat.clinical-impression') : ($item === 'Medication Request' ? route('satu-sehat.medication-request') : ($item === 'Medication Dispense' ? route('satu-sehat.medication-dispense') : ($item === 'Medication Statement' ? route('satu-sehat.medication-statement') : ($item === 'Care Plan' ? route('satu-sehat.care-plan') : '#')))))))) }}" class="flex items-center gap-3 rounded-xl px-2 py-2.5 hover:bg-white/5">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                                <svg class="h-5 w-5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="8" />
                                                    <path d="M12 8v8" />
                                                    <path d="M8 12h8" />
                                                </svg>
                                                <span>{{ $item }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </section>

                            <section>
                                <div class="rounded-2xl px-0">
                                    <button type="button" class="flex w-full cursor-pointer items-center gap-2.5 rounded-2xl px-0 py-1.5 text-left text-white" data-dropdown-toggle aria-expanded="true">
                                        <span class="flex h-11 w-11 items-center justify-center text-sky-200">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M4 20V10" />
                                                <path d="M10 20V4" />
                                                <path d="M16 20v-7" />
                                                <path d="M22 20v-3" />
                                            </svg>
                                        </span>
                                        <span class="min-w-0 flex-1 text-sm font-semibold">Analitik</span>
                                        <div class="ml-auto flex shrink-0 items-center gap-3">
                                            <div class="h-px w-24 bg-white/10"></div>
                                            <svg class="h-5 w-5 text-slate-400 transition duration-200" data-dropdown-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </div>
                                    </button>
                                    <div class="mt-1 space-y-1 pl-[7px] text-sm text-slate-300" data-dropdown-panel>
                                        @foreach (['Capaian Bridging', 'SLA Pelayanan', 'Waktu Tunggu', 'Analisis Rujukan', 'Analisis TACC', 'Statistik Dokter', 'Statistik Poli', 'Grafik Capaian'] as $item)
                                            <a href="#" class="flex items-center gap-3 rounded-xl px-2 py-2.5 hover:bg-white/5">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                                <svg class="h-5 w-5 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="8" />
                                                    <path d="M12 12 16 8" />
                                                </svg>
                                                <span>{{ $item }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </section>
                        </div>
                        @include('partials.setup-clinic-menu')
                    </nav>
                </div>
            </aside>

            <main class="bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.08),_transparent_25%),linear-gradient(180deg,_#f8fafc_0%,_#eef4fb_100%)]">
                <div class="app-page-header">
                    <div class="flex items-center justify-between px-3 py-2.5 lg:px-3">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Kendala Bridging</h2>
                            <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                <span class="text-cyan-600">Monitor BPJS</span>
                                <span>&gt;</span>
                                <span>Kendala Bridging</span>
                            </div>
                        </div>
                        @include('partials.clinic-selector')
                    </div>
                </div>

                <div class="px-3 py-4 lg:px-3">
                    <div class="grid gap-2.5 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-3xl border border-slate-200 bg-white p-3.5 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M8 7h8" />
                                        <path d="M8 12h8" />
                                        <path d="M8 17h4" />
                                        <path d="M6 3h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Total Kendala</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $summary['total'] }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-white p-3.5 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="8" />
                                        <path d="m8.8 12 2.2 2.2 4.5-4.5" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Same Day</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $summary['same_day'] }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-white p-3.5 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 6v6l4 2" />
                                        <circle cx="12" cy="12" r="9" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Beda Hari</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $summary['cross_day'] }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-white p-3.5 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 9v4" />
                                        <path d="M12 17h.01" />
                                        <path d="M10.3 3.24 1.82 18a2 2 0 0 0 1.74 3h16.88a2 2 0 0 0 1.74-3L13.7 3.24a2 2 0 0 0-3.4 0Z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">No Kunjungan Kosong</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $summary['no_kunjungan_kosong'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($usingFallback)
                        <div class="mt-2.5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                            Data kendala bridging masih memakai fallback lokal karena koneksi database klinik belum berhasil.
                            @if ($dbError)
                                <div class="mt-1 font-mono text-[11px]">{{ $dbError }}</div>
                            @endif
                        </div>
                    @endif

                    <div class="mt-2.5 rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                        <form method="GET" action="{{ route('monitoring.kendala-bridging') }}" class="flex flex-nowrap items-end gap-2">
                            <div class="shrink-0" style="width: 29.5%;">
                                <label class="mb-1.5 block text-xs font-medium text-slate-700">Cari Pasien</label>
                                <input type="text" value="" placeholder="Cari nama / no rawat / no RM..." data-live-search data-search-empty-message="Tidak ada data kendala bridging yang cocok." class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-100">
                            </div>
                            <div class="min-w-0 flex-1"></div>
                            <div class="shrink-0" style="width: 18.1%;">
                                <label class="mb-1.5 block text-xs font-medium text-slate-700">Tanggal Awal</label>
                                <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-100">
                            </div>
                            <div class="shrink-0" style="width: 18.1%;">
                                <label class="mb-1.5 block text-xs font-medium text-slate-700">Tanggal Akhir</label>
                                <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-100">
                            </div>
                            <div class="flex shrink-0 items-end justify-end gap-2">
                                <button type="submit" class="inline-flex h-[35px] cursor-pointer items-center justify-center rounded-2xl bg-blue-600 px-4 text-xs font-semibold text-white transition hover:bg-blue-700">
                                    Filter
                                </button>
                                <a href="{{ route('monitoring.kendala-bridging') }}" class="inline-flex h-[35px] items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                                    Reset
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="mt-2.5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-xs">
                                <colgroup>
                                    <col class="w-14">
                                    <col class="w-52">
                                    <col class="w-56">
                                </colgroup>
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="w-14 px-3 py-3.5 text-center font-semibold">No.</th>
                                        <th class="w-52 px-3 py-3.5 font-semibold">No Rawat</th>
                                        <th class="w-56 px-3 py-3.5 font-semibold">Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">Poliklinik</th>
                                        <th class="px-3 py-3.5 font-semibold">Registrasi</th>
                                        <th class="px-3 py-3.5 font-semibold">PCare Daftar</th>
                                        <th class="px-3 py-3.5 font-semibold">PCare Kunjungan</th>
                                        <th class="px-3 py-3.5 font-semibold">No Kunjungan</th>
                                        <th class="px-3 py-3.5 text-center font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($kendalaRows as $index => $row)
                                        <tr class="hover:bg-slate-50/80" data-search-row data-search-text="{{ strtolower($row['no_rawat'].' '.$row['norm'].' '.$row['pasien'].' '.$row['poli'].' '.$row['status_hari']) }}">
                                            <td class="w-14 px-3 py-3.5 text-center text-slate-500">{{ $index + 1 }}</td>
                                            <td class="w-52 px-3 py-3.5">
                                                <p class="font-medium text-slate-900">{{ $row['no_rawat'] }}</p>
                                                <p class="mt-1 text-xs text-slate-500">RM {{ $row['norm'] }}</p>
                                            </td>
                                            <td class="w-56 px-3 py-3.5 font-medium text-slate-900">{{ $row['pasien'] }}</td>
                                            <td class="px-3 py-3.5 text-slate-700">{{ $row['poli'] }}</td>
                                            <td class="px-3 py-3.5">
                                                <p class="font-medium text-slate-900">{{ $row['reg_tanggal'] }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $row['reg_jam'] }}</p>
                                            </td>
                                            <td class="px-3 py-3.5 text-slate-700">{{ $row['pcare_daftar'] }}</td>
                                            <td class="px-3 py-3.5 text-slate-700">{{ $row['pcare_kunjungan'] }}</td>
                                            <td class="px-3 py-3.5">
                                                <span class="inline-flex rounded-full bg-rose-50 px-3 py-1 text-[11px] font-semibold text-rose-700">
                                                    {{ $row['no_kunjungan'] }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3.5 text-center">
                                                <form method="POST" action="{{ route('monitoring.kendala-bridging.perbaiki', ['start_date' => $filters['start_date'], 'end_date' => $filters['end_date']]) }}" class="inline-flex" data-perbaiki-form data-no-rawat="{{ $row['no_rawat'] }}">
                                                    @csrf
                                                    <input type="hidden" name="no_rawat" value="{{ $row['no_rawat'] }}">
                                                    <button type="button" title="Perbaiki" aria-label="Perbaiki" data-perbaiki-trigger class="group inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border border-emerald-200/90 bg-gradient-to-br from-emerald-50 to-white text-emerald-700 shadow-sm transition hover:border-emerald-300 hover:from-emerald-100 hover:to-emerald-50 hover:text-emerald-800">
                                                        <svg class="h-5 w-5 transition-transform duration-300 group-hover:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M4.5 12a7.5 7.5 0 0 1 12.8-5.3L19 8.4" />
                                                            <path d="M19 4.8v3.6h-3.6" />
                                                            <path d="M19.5 12a7.5 7.5 0 0 1-12.8 5.3L5 15.6" />
                                                            <path d="M5 19.2v-3.6h3.6" />
                                                            <path d="M12 9.4v5.2" />
                                                            <path d="M9.4 12h5.2" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="px-3 py-8 text-center text-slate-500">
                                                Tidak ada data kendala bridging pada filter yang dipilih.
                                            </td>
                                        </tr>
                                    @endforelse
                                    <tr class="hidden" data-search-empty-row>
                                        <td colspan="10" class="px-3 py-8 text-center text-slate-500">Tidak ada data kendala bridging yang cocok.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <div id="perbaiki-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/45 backdrop-blur-[2px]" data-modal-close></div>
            <div class="relative w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.22)]">
                <div class="bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_36%),linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 shadow-sm">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4.5 12a7.5 7.5 0 0 1 12.8-5.3L19 8.4" />
                                <path d="M19 4.8v3.6h-3.6" />
                                <path d="M19.5 12a7.5 7.5 0 0 1-12.8 5.3L5 15.6" />
                                <path d="M5 19.2v-3.6h3.6" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">Perbaiki Kendala Bridging</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Tindakan ini akan menghapus data kunjungan PCare agar bisa dilakukan bridging ulang dari klinik.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">No Rawat</p>
                        <p id="perbaiki-modal-no-rawat" class="mt-1 text-sm font-semibold text-slate-900">-</p>
                    </div>
                    <div class="mt-5 flex items-center justify-end gap-3">
                        <button type="button" id="perbaiki-cancel" class="inline-flex h-10 cursor-pointer items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="button" id="perbaiki-confirm" class="inline-flex h-10 cursor-pointer items-center justify-center rounded-2xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            Ya, Perbaiki
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @if (session('success') || session('error'))
            <div
                id="flash-toast"
                class="pointer-events-none fixed right-4 bottom-4 z-50 w-full max-w-sm transition duration-300 sm:right-6 sm:bottom-6"
            >
                <div class="pointer-events-auto overflow-hidden rounded-3xl border {{ session('success') ? 'border-emerald-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#f0fdf4_100%)]' : 'border-rose-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#fff1f2_100%)]' }} shadow-[0_24px_80px_rgba(15,23,42,0.18)]" data-flash-toast>
                    <div class="flex items-start gap-4 px-5 py-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ session('success') ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                            @if (session('success'))
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="m8.5 12 2.3 2.3 4.7-4.8" />
                                </svg>
                            @else
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 8v4" />
                                    <path d="M12 16h.01" />
                                    <path d="M10.3 3.24 1.82 18a2 2 0 0 0 1.74 3h16.88a2 2 0 0 0 1.74-3L13.7 3.24a2 2 0 0 0-3.4 0Z" />
                                </svg>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold {{ session('success') ? 'text-emerald-900' : 'text-rose-900' }}">
                                {{ session('success') ? 'Berhasil Diperbarui' : 'Aksi Gagal' }}
                            </p>
                            <p class="mt-1 text-xs leading-5 {{ session('success') ? 'text-emerald-800' : 'text-rose-800' }}">
                                {{ session('success') ?? session('error') }}
                            </p>
                        </div>
                        <button type="button" class="mt-0.5 inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-xl text-slate-400 transition hover:bg-white/70 hover:text-slate-600" data-flash-close>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="h-1.5 w-full {{ session('success') ? 'bg-emerald-100' : 'bg-rose-100' }}">
                        <div class="h-full origin-left {{ session('success') ? 'bg-emerald-500' : 'bg-rose-500' }}" data-flash-progress></div>
                    </div>
                </div>
            </div>
        @endif
        <script>
            document.querySelectorAll('[data-dropdown-toggle]').forEach((button) => {
                const panel = button.parentElement.querySelector('[data-dropdown-panel]');
                const icon = button.querySelector('[data-dropdown-icon]');

                if (!panel || !icon) {
                    return;
                }

                const setOpen = (open) => {
                    panel.classList.toggle('hidden', !open);
                    icon.classList.toggle('rotate-180', open);
                    button.setAttribute('aria-expanded', open ? 'true' : 'false');
                };

                setOpen(false);

                button.addEventListener('click', () => {
                    const isOpen = button.getAttribute('aria-expanded') === 'true';
                    setOpen(!isOpen);
                });
            });

            document.querySelectorAll('[data-live-search]').forEach((input) => {
                const table = input.closest('main')?.querySelector('table');
                const rows = table ? Array.from(table.querySelectorAll('[data-search-row]')) : [];
                const emptyRow = table?.querySelector('[data-search-empty-row]');

                const applyFilter = () => {
                    const keyword = input.value.trim().toLowerCase();
                    let visibleCount = 0;

                    rows.forEach((row) => {
                        const haystack = row.dataset.searchText || '';
                        const matched = keyword === '' || haystack.includes(keyword);
                        row.classList.toggle('hidden', !matched);
                        if (matched) {
                            visibleCount += 1;
                        }
                    });

                    if (emptyRow) {
                        emptyRow.classList.toggle('hidden', visibleCount !== 0);
                    }
                };

                input.addEventListener('input', applyFilter);
                applyFilter();
            });

            (() => {
                const modal = document.getElementById('perbaiki-modal');
                const label = document.getElementById('perbaiki-modal-no-rawat');
                const confirmButton = document.getElementById('perbaiki-confirm');
                const cancelButton = document.getElementById('perbaiki-cancel');
                const closeTargets = modal ? Array.from(modal.querySelectorAll('[data-modal-close]')) : [];
                let activeForm = null;

                if (!modal || !label || !confirmButton || !cancelButton) {
                    return;
                }

                const openModal = (form) => {
                    activeForm = form;
                    label.textContent = form.dataset.noRawat || '-';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                };

                const closeModal = () => {
                    activeForm = null;
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');
                };

                document.querySelectorAll('[data-perbaiki-trigger]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const form = button.closest('[data-perbaiki-form]');
                        if (!form) {
                            return;
                        }

                        openModal(form);
                    });
                });

                confirmButton.addEventListener('click', () => {
                    if (!activeForm) {
                        return;
                    }

                    const formToSubmit = activeForm;
                    closeModal();
                    formToSubmit.submit();
                });

                cancelButton.addEventListener('click', closeModal);
                closeTargets.forEach((target) => target.addEventListener('click', closeModal));

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeModal();
                    }
                });
            })();

            (() => {
                const toastHost = document.getElementById('flash-toast');
                const toast = toastHost?.querySelector('[data-flash-toast]');
                const closeButton = toastHost?.querySelector('[data-flash-close]');
                const progress = toastHost?.querySelector('[data-flash-progress]');

                if (!toastHost || !toast || !closeButton || !progress) {
                    return;
                }

                const closeToast = () => {
                    toastHost.classList.add('translate-y-3', 'opacity-0');
                    setTimeout(() => {
                        toastHost.remove();
                    }, 280);
                };

                progress.animate(
                    [
                        { transform: 'scaleX(1)' },
                        { transform: 'scaleX(0)' },
                    ],
                    {
                        duration: 4200,
                        easing: 'linear',
                        fill: 'forwards',
                    }
                );

                const timer = setTimeout(closeToast, 4200);

                closeButton.addEventListener('click', () => {
                    clearTimeout(timer);
                    closeToast();
                });
            })();
        </script>
        <script src="{{ asset('js/sidebar-state.js') }}"></script>
    </body>
</html>
