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
                                        <a href="{{ url('/') }}" class="flex items-center gap-2.5 rounded-xl px-2 py-2.5 text-sm text-slate-300 hover:bg-white/5">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                            <svg class="h-5 w-5 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m22 2-7 20-4-9-9-4Z" />
                                                <path d="M22 2 11 13" />
                                            </svg>
                                            <span>Bridging Pasien</span>
                                        </a>
                                        <a href="{{ route('monitoring.timeline-pelayanan') }}" class="flex items-center gap-2.5 rounded-xl bg-cyan-400/8 px-2 py-2.5 text-sm text-white ring-1 ring-cyan-400/10">
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
                                        <a href="{{ route('monitoring.kendala-bridging') }}" class="flex items-center gap-2.5 rounded-xl px-2 py-2.5 text-sm text-slate-300 hover:bg-white/5">
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

                            <section>
                                <div class="rounded-2xl px-0">
                                    <button type="button" class="flex w-full cursor-pointer items-center gap-2.5 rounded-2xl px-0 py-1.5 text-left text-white" data-dropdown-toggle aria-expanded="true">
                                        <span class="flex h-11 w-11 items-center justify-center text-emerald-200">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                                <path d="M14 2v6h6" />
                                                <path d="M8 13h8" />
                                                <path d="M8 17h6" />
                                            </svg>
                                        </span>
                                        <span class="min-w-0 flex-1 text-sm font-semibold">Laporan</span>
                                        <div class="ml-auto flex shrink-0 items-center gap-3">
                                            <div class="h-px w-24 bg-white/10"></div>
                                            <svg class="h-5 w-5 text-slate-400 transition duration-200" data-dropdown-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </div>
                                    </button>
                                    <div class="mt-1 space-y-1 pl-[7px] text-sm text-slate-300" data-dropdown-panel>
                                        @foreach (['Rekap Harian', 'Rekap Bulanan', 'Rekap Antrean BPJS', 'Rekap Bridging Pelayanan', 'Rekap Kendala Bridging', 'Rekap Waktu Pelayanan', 'Audit Bridging', 'Export Data'] as $item)
                                            <a href="#" class="flex items-center gap-3 rounded-xl px-2 py-2.5 hover:bg-white/5">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                                <svg class="h-5 w-5 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M8 2v4" />
                                                    <path d="M16 2v4" />
                                                    <rect width="18" height="18" x="3" y="4" rx="2" />
                                                    <path d="M3 10h18" />
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
                                        <span class="flex h-11 w-11 items-center justify-center text-amber-200">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                                                <path d="m9 12 2 2 4-4" />
                                            </svg>
                                        </span>
                                        <span class="min-w-0 flex-1 text-sm font-semibold">Audit Log</span>
                                        <div class="ml-auto flex shrink-0 items-center gap-3">
                                            <div class="h-px w-24 bg-white/10"></div>
                                            <svg class="h-5 w-5 text-slate-400 transition duration-200" data-dropdown-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </div>
                                    </button>
                                    <div class="mt-1 space-y-1 pl-[7px] text-sm text-slate-300" data-dropdown-panel>
                                        @foreach (['Log Antrean BPJS', 'Log Bridging Pelayanan', 'Log Request API', 'Log Response API', 'Riwayat Retry Bridging', 'Aktivitas Pengguna'] as $item)
                                            <a href="#" class="flex items-center gap-3 rounded-xl px-2 py-2.5 hover:bg-white/5">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                                <svg class="h-5 w-5 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M8 7h8" />
                                                    <path d="M8 12h8" />
                                                    <path d="M8 17h5" />
                                                    <path d="M4 7h.01" />
                                                    <path d="M4 12h.01" />
                                                    <path d="M4 17h.01" />
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
                                        <span class="flex h-11 w-11 items-center justify-center text-indigo-200">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <ellipse cx="12" cy="5" rx="7" ry="3" />
                                                <path d="M5 5v6c0 1.66 3.13 3 7 3s7-1.34 7-3V5" />
                                                <path d="M5 11v6c0 1.66 3.13 3 7 3s7-1.34 7-3v-6" />
                                            </svg>
                                        </span>
                                        <span class="min-w-0 flex-1 text-sm font-semibold">Master Data</span>
                                        <div class="ml-auto flex shrink-0 items-center gap-3">
                                            <div class="h-px w-24 bg-white/10"></div>
                                            <svg class="h-5 w-5 text-slate-400 transition duration-200" data-dropdown-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </div>
                                    </button>
                                    <div class="mt-1 space-y-1 pl-[7px] text-sm text-slate-300" data-dropdown-panel>
                                        @foreach (['Mapping Dokter BPJS', 'Mapping Poli BPJS', 'Mapping Diagnosa (ICD-10)', 'Mapping Tindakan', 'Mapping Status Pulang', 'Jadwal Dokter'] as $item)
                                            <a href="#" class="flex items-center gap-3 rounded-xl px-2 py-2.5 hover:bg-white/5">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                                <svg class="h-5 w-5 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 12h.01" />
                                                    <path d="M16 6h.01" />
                                                    <path d="M8 18h.01" />
                                                    <path d="M17 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
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
                                        <span class="flex h-11 w-11 items-center justify-center text-slate-200">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="3" />
                                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1 .6 1.65 1.65 0 0 0-.33 1V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-.33-1 1.65 1.65 0 0 0-1-.6 1.65 1.65 0 0 0-1.82.33l-.06.06A2 2 0 1 1 3.63 16l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-.6-1 1.65 1.65 0 0 0-1-.33H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1-.33 1.65 1.65 0 0 0 .6-1 1.65 1.65 0 0 0-.33-1.82L4.3 6.46A2 2 0 1 1 7.12 3.64l.06.06A1.65 1.65 0 0 0 9 4.6c.39-.14.74-.39 1-.72.2-.27.31-.6.33-.94V3a2 2 0 1 1 4 0v.09c.02.34.13.67.33.94.26.33.61.58 1 .72a1.65 1.65 0 0 0 1.82-.33l.06-.06A2 2 0 1 1 20.36 7.18l-.06.06c-.46.46-.6 1.14-.33 1.82.14.39.39.74.72 1 .27.2.6.31.94.33H21a2 2 0 1 1 0 4h-.09c-.34.02-.67.13-.94.33-.33.26-.58.61-.72 1Z" />
                                            </svg>
                                        </span>
                                        <span class="min-w-0 flex-1 text-sm font-semibold">Pengaturan</span>
                                        <div class="ml-auto flex shrink-0 items-center gap-3">
                                            <div class="h-px w-24 bg-white/10"></div>
                                            <svg class="h-5 w-5 text-slate-400 transition duration-200" data-dropdown-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </div>
                                    </button>
                                    <div class="mt-1 space-y-1 pl-[7px] text-sm text-slate-300" data-dropdown-panel>
                                        @foreach (['Target SLA Pelayanan', 'Konfigurasi Bridging', 'Retry Bridging Otomatis', 'Notifikasi Monitoring', 'Hak Akses Pengguna', 'Parameter Sistem'] as $item)
                                            <a href="#" class="flex items-center gap-3 rounded-xl px-2 py-2.5 hover:bg-white/5">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                                <svg class="h-5 w-5 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 2v4" />
                                                    <path d="M12 18v4" />
                                                    <path d="m4.93 4.93 2.83 2.83" />
                                                    <path d="m16.24 16.24 2.83 2.83" />
                                                    <path d="M2 12h4" />
                                                    <path d="M18 12h4" />
                                                    <path d="m4.93 19.07 2.83-2.83" />
                                                    <path d="m16.24 7.76 2.83-2.83" />
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
                            <h2 class="text-base font-semibold text-slate-900">Timeline Pelayanan</h2>
                            <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                <span class="text-cyan-600">Monitor BPJS</span>
                                <span>&gt;</span>
                                <span>Timeline Pelayanan</span>
                            </div>
                        </div>
                        @include('partials.clinic-selector')
                    </div>
                </div>

                <div class="px-3 py-4 lg:px-3">
                    @php
                        $isRujukan = $filters['jenis'] === 'rujukan';
                        $baseTabQuery = request()->except('tahap');
                        $rawatJalanQuery = array_merge($baseTabQuery, ['jenis' => 'rawat-jalan']);
                        $rujukanQuery = array_merge($baseTabQuery, ['jenis' => 'rujukan']);
                    @endphp

                    <div class="mb-8 rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('monitoring.timeline-pelayanan', $rawatJalanQuery) }}" class="@if(! $isRujukan) bg-blue-600 text-white shadow-sm @else bg-slate-100 text-slate-600 hover:bg-slate-200 @endif inline-flex items-center rounded-2xl px-4 py-2 text-xs font-semibold transition">
                                Timeline Rawat Jalan
                            </a>
                            <a href="{{ route('monitoring.timeline-pelayanan', $rujukanQuery) }}" class="@if($isRujukan) bg-blue-600 text-white shadow-sm @else bg-slate-100 text-slate-600 hover:bg-slate-200 @endif inline-flex items-center rounded-2xl px-4 py-2 text-xs font-semibold transition">
                                Timeline Rujukan
                            </a>
                        </div>
                    </div>

                    <div class="mt-1 grid gap-2.5 md:grid-cols-2 xl:grid-cols-4">
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
                                    <p class="text-xs text-slate-500">Total Timeline</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $summary['total'] }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-white p-3.5 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="8" />
                                        <path d="M12 8v4l2.5 2.5" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">{{ $isRujukan ? 'Dengan TACC' : 'Baru Registrasi' }}</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $isRujukan ? $summary['tacc'] : $summary['registrasi'] }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-white p-3.5 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M8 6h13" />
                                        <path d="M8 12h13" />
                                        <path d="M8 18h13" />
                                        <path d="M3 6h.01" />
                                        <path d="M3 12h.01" />
                                        <path d="M3 18h.01" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">{{ $isRujukan ? 'Tanpa TACC' : 'Sudah PCare Daftar' }}</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $isRujukan ? $summary['tanpa_tacc'] : $summary['pendaftaran'] }}</p>
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
                                    <p class="text-xs text-slate-500">{{ $isRujukan ? 'Rujuk Beda Hari' : 'Bridging Beda Hari' }}</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $summary['cross_day'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($usingFallback)
                        <div class="mt-2.5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                            Data timeline masih memakai fallback lokal karena koneksi database klinik belum berhasil.
                            @if ($dbError)
                                <div class="mt-1 font-mono text-[11px]">{{ $dbError }}</div>
                            @endif
                        </div>
                    @endif

                    <div class="mt-2.5 rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                        <form method="GET" action="{{ route('monitoring.timeline-pelayanan') }}" class="grid gap-2 lg:grid-cols-[minmax(0,1.3fr)_repeat(2,minmax(0,0.8fr))_minmax(0,0.95fr)_auto]">
                            <input type="hidden" name="jenis" value="{{ $filters['jenis'] }}">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-700">Cari Pasien</label>
                                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Cari nama / no rawat / no RM..." class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-100">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-700">Tanggal Awal</label>
                                <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-100">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-700">Tanggal Akhir</label>
                                <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-100">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-700">{{ $isRujukan ? 'Status Rujukan' : 'Tahap Akhir' }}</label>
                                <select name="tahap" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-100">
                                    @if ($isRujukan)
                                        <option value="all" @selected($filters['tahap'] === 'all')>Semua</option>
                                        <option value="tacc" @selected($filters['tahap'] === 'tacc')>Dengan TACC</option>
                                        <option value="tanpa_tacc" @selected($filters['tahap'] === 'tanpa_tacc')>Tanpa TACC</option>
                                    @else
                                        <option value="all" @selected($filters['tahap'] === 'all')>Semua</option>
                                        <option value="registrasi" @selected($filters['tahap'] === 'registrasi')>Registrasi</option>
                                        <option value="pendaftaran" @selected($filters['tahap'] === 'pendaftaran')>Pendaftaran PCare</option>
                                        <option value="kunjungan" @selected($filters['tahap'] === 'kunjungan')>Kunjungan Selesai</option>
                                    @endif
                                </select>
                            </div>
                            <div class="flex items-end gap-2">
                                <button type="submit" class="inline-flex h-[35px] cursor-pointer items-center justify-center rounded-2xl bg-blue-600 px-4 text-xs font-semibold text-white transition hover:bg-blue-700">
                                    Filter
                                </button>
                                <a href="{{ route('monitoring.timeline-pelayanan', ['jenis' => $filters['jenis']]) }}" class="inline-flex h-[35px] items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
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
                                        <th class="px-3 py-3.5 text-center font-semibold">Poliklinik</th>
                                        <th class="px-3 py-3.5 text-center font-semibold">Registrasi</th>
                                        @if ($isRujukan)
                                            <th class="px-3 py-3.5 text-center font-semibold">Tgl Rujuk</th>
                                            <th class="px-3 py-3.5 text-center font-semibold">Subspesialis / PPK</th>
                                        @else
                                            <th class="px-3 py-3.5 text-center font-semibold">PCare Daftar</th>
                                            <th class="px-3 py-3.5 text-center font-semibold">PCare Kunjungan</th>
                                        @endif
                                        <th class="px-3 py-3.5 text-center font-semibold">Status</th>
                                        <th class="px-3 py-3.5 text-center font-semibold">{{ $isRujukan ? 'TACC / Estimasi' : 'Total Tunggu' }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($timelineRows as $index => $row)
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="w-14 px-3 py-3.5 text-center text-slate-500">{{ $index + 1 }}</td>
                                            <td class="w-52 px-3 py-3.5">
                                                <p class="font-medium text-slate-900">{{ $row['no_rawat'] }}</p>
                                                <p class="mt-1 text-xs text-slate-500">RM {{ $row['norm'] }}</p>
                                            </td>
                                            <td class="w-56 px-3 py-3.5">
                                                <p class="font-medium text-slate-900">{{ $row['pasien'] }}</p>
                                            </td>
                                            <td class="px-3 py-3.5 text-center text-slate-700">{{ $row['poli'] }}</td>
                                            <td class="px-3 py-3.5 text-center">
                                                <p class="font-medium text-slate-900">{{ $row['reg_tanggal'] }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $row['reg_jam'] }}</p>
                                            </td>
                                            @if ($isRujukan)
                                                <td class="px-3 py-3.5 text-center text-slate-700">
                                                    <p>{{ $row['tgl_rujuk'] ?? '-' }}</p>
                                                    <p class="mt-1 text-[11px] text-slate-500">{{ $row['total_tunggu_label'] }}</p>
                                                </td>
                                                <td class="px-3 py-3.5 text-center">
                                                    <p class="font-medium text-slate-900">{{ $row['subspesialis'] }}</p>
                                                    <p class="mt-1 text-[11px] text-slate-500">{{ $row['ppk_rujuk'] }}</p>
                                                </td>
                                            @else
                                                <td class="px-3 py-3.5 text-center text-slate-700">{{ $row['pcare_daftar'] ?? '-' }}</td>
                                                <td class="px-3 py-3.5 text-center text-slate-700">{{ $row['pcare_kunjungan'] ?? '-' }}</td>
                                            @endif
                                            <td class="px-3 py-3.5 text-center">
                                                <span class="@if($row['warna'] === 'emerald') bg-emerald-50 text-emerald-700 @elseif($row['warna'] === 'amber') bg-amber-50 text-amber-700 @elseif($row['warna'] === 'blue') bg-blue-50 text-blue-700 @else bg-slate-100 text-slate-700 @endif inline-flex rounded-full px-3 py-1 text-[11px] font-semibold">
                                                    {{ $row['tahap_akhir'] }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3.5 text-center">
                                                @if ($isRujukan)
                                                    <p class="font-semibold text-slate-900">{{ $row['tacc'] }}</p>
                                                    <p class="mt-1 text-[11px] text-slate-500">{{ $row['tgl_estimasi_rujuk'] ?: '-' }}</p>
                                                @else
                                                    <p class="font-semibold text-slate-900">{{ $row['total_tunggu_label'] }}</p>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-3 py-8 text-center text-slate-500">
                                                Tidak ada data timeline pelayanan pada filter yang dipilih.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
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

        </script>
        <script src="{{ asset('js/sidebar-state.js') }}"></script>
    </body>
</html>
