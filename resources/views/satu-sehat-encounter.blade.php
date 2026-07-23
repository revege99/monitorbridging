<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Monitoring Bridging') }}</title>

        @fonts
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    </head>
    @php
        $isCondition = ($resourceType ?? 'encounter') === 'condition';
        $isObservation = ($resourceType ?? 'encounter') === 'observation';
        $isProcedure = ($resourceType ?? 'encounter') === 'procedure';
        $isClinicalImpression = ($resourceType ?? 'encounter') === 'clinical-impression';
        $isMedicationRequest = ($resourceType ?? 'encounter') === 'medication-request';
        $isMedicationDispense = ($resourceType ?? 'encounter') === 'medication-dispense';
        $isMedicationStatement = ($resourceType ?? 'encounter') === 'medication-statement';
        $isCarePlan = ($resourceType ?? 'encounter') === 'care-plan';
        $pageTitle = $isCondition ? 'Condition' : ($isObservation ? 'Observation' : ($isProcedure ? 'Procedure' : ($isClinicalImpression ? 'Clinical Impression' : ($isMedicationRequest ? 'Medication Request' : ($isMedicationDispense ? 'Medication Dispense' : ($isMedicationStatement ? 'Medication Statement' : ($isCarePlan ? 'Care Plan' : 'Encounter')))))));
        $pageRoute = $isCondition ? 'satu-sehat.condition' : ($isObservation ? 'satu-sehat.observation' : ($isProcedure ? 'satu-sehat.procedure' : ($isClinicalImpression ? 'satu-sehat.clinical-impression' : ($isMedicationRequest ? 'satu-sehat.medication-request' : ($isMedicationDispense ? 'satu-sehat.medication-dispense' : ($isMedicationStatement ? 'satu-sehat.medication-statement' : ($isCarePlan ? 'satu-sehat.care-plan' : 'satu-sehat.encounter')))))));
    @endphp
    <body class="min-h-screen bg-[#f4f7fb] text-slate-900 lg:h-dvh lg:overflow-hidden">
        <div class="min-h-screen lg:grid lg:h-dvh lg:grid-cols-[220px_minmax(0,1fr)] lg:overflow-hidden">
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
                                <a href="{{ url('/') }}" class="relative flex items-center gap-2 overflow-hidden rounded-xl px-2 py-2 text-sm font-semibold text-slate-300 hover:bg-white/5">
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
                                            <a href="{{ $item === 'Encounter' ? route('satu-sehat.encounter') : ($item === 'Condition' ? route('satu-sehat.condition') : ($item === 'Observation' ? route('satu-sehat.observation') : ($item === 'Procedure' ? route('satu-sehat.procedure') : ($item === 'Clinical Impression' ? route('satu-sehat.clinical-impression') : ($item === 'Medication Request' ? route('satu-sehat.medication-request') : ($item === 'Medication Dispense' ? route('satu-sehat.medication-dispense') : ($item === 'Medication Statement' ? route('satu-sehat.medication-statement') : ($item === 'Care Plan' ? route('satu-sehat.care-plan') : '#')))))))) }}" class="flex items-center gap-3 rounded-xl px-2 py-2.5 text-sm {{ $item === $pageTitle ? 'bg-cyan-400/8 text-white ring-1 ring-cyan-400/10' : 'text-slate-300 hover:bg-white/5' }}">
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
                        </div>
                        @include('partials.sidebar-secondary-groups')
                        @include('partials.setup-clinic-menu')
                    </nav>
                </div>
            </aside>

            <main class="bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.08),_transparent_25%),linear-gradient(180deg,_#f8fafc_0%,_#eef4fb_100%)] lg:flex lg:h-dvh lg:min-h-0 lg:flex-col lg:overflow-hidden">
                <div class="app-page-header">
                    <div class="flex items-center justify-between px-3 py-2.5 lg:px-3">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">{{ $pageTitle }}</h2>
                            <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                <span class="text-cyan-600">Satu Sehat</span>
                                <span>&gt;</span>
                                <span>{{ $pageTitle }}</span>
                            </div>
                        </div>
                        @include('partials.clinic-selector')
                    </div>
                </div>

                <div class="px-3 pt-0.5 pb-[11px] lg:flex lg:min-h-0 lg:flex-1 lg:flex-col lg:overflow-hidden lg:px-3">
                    @if ($usingFallback)
                        <div class="mt-2.5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                            Data {{ strtolower($pageTitle) }} tidak dapat dimuat karena koneksi atau struktur database klinik bermasalah.
                            @if ($dbError)
                                <div class="mt-1 font-mono text-[11px]">{{ $dbError }}</div>
                            @endif
                        </div>
                    @endif

                    <div class="mt-1">
                        <form method="GET" action="{{ route($pageRoute) }}" class="flex flex-nowrap items-center gap-2">
                            @if ($isObservation)
                                <input type="hidden" name="tab" value="{{ $activeObservationTab }}">
                            @endif
                            <div class="flex shrink-0 items-center gap-2" style="width: 44%;">
                                <label class="shrink-0 text-xs font-medium text-slate-700">Cari Data</label>
                                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="{{ $isCondition ? 'Cari no rawat / RM / pasien / kode / diagnosis...' : ($isProcedure ? 'Cari no rawat / RM / pasien / kode / prosedur...' : ($isClinicalImpression ? 'Cari no rawat / RM / pasien / praktisi / penilaian...' : ($isCarePlan ? 'Cari no rawat / RM / pasien / praktisi / rencana...' : (($isMedicationRequest || $isMedicationDispense || $isMedicationStatement) ? 'Cari no rawat / RM / pasien / kode / nama obat...' : 'Cari no rawat / RM / pasien / dokter / poli...')))) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-100">
                            </div>
                            <div class="min-w-0 flex-1"></div>
                            <div class="flex shrink-0 items-center gap-2" style="width: 19%;">
                                <label class="shrink-0 text-xs font-medium text-slate-700">Tanggal Awal</label>
                                <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-100">
                            </div>
                            <div class="flex shrink-0 items-center gap-2" style="width: 19%;">
                                <label class="shrink-0 text-xs font-medium text-slate-700">Tanggal Akhir</label>
                                <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-100">
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <button type="submit" class="inline-flex h-[35px] cursor-pointer items-center justify-center rounded-2xl bg-blue-600 px-4 text-xs font-semibold text-white transition hover:bg-blue-700">
                                    Filter
                                </button>
                                <a href="{{ route($pageRoute) }}" class="inline-flex h-[35px] items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                                    Reset
                                </a>
                            </div>
                        </form>
                    </div>

                    @if ($isObservation)
                        <div class="mt-2 flex shrink-0 gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                            @foreach ($observationTabs as $tabKey => $tabLabel)
                                <a href="{{ route('satu-sehat.observation', array_filter([
                                    'tab' => $tabKey,
                                    'search' => $filters['search'],
                                    'start_date' => $filters['start_date'],
                                    'end_date' => $filters['end_date'],
                                ])) }}" class="inline-flex h-8 shrink-0 items-center justify-center rounded-lg px-3 text-xs font-semibold transition {{ $activeObservationTab === $tabKey ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                                    {{ $tabLabel }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-2.5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:min-h-0 lg:flex-1">
                        <div class="min-h-[260px] overflow-auto lg:h-full lg:min-h-0">
                                <table class="compact-data-table {{ $isMedicationDispense ? 'min-w-[4200px]' : (($isMedicationRequest || $isMedicationStatement) ? 'min-w-[3800px]' : 'min-w-[2600px]') }} whitespace-nowrap text-left text-xs" data-encounter-table>
                                <thead class="sticky top-0 z-[1] bg-slate-50 text-slate-600">
                                    <tr>
                                    @if ($isObservation)
                                        <th class="px-3 py-3.5 font-semibold">No.</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Pemeriksaan</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Registrasi</th>
                                        <th class="px-3 py-3.5 font-semibold">No Rawat</th>
                                        <th class="px-3 py-3.5 font-semibold">No. RM</th>
                                        <th class="px-3 py-3.5 font-semibold">Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Periksa</th>
                                        <th class="px-3 py-3.5 font-semibold">Jenis Layanan</th>
                                        <th class="px-3 py-3.5 font-semibold">Pulang</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Encounter</th>
                                        <th class="px-3 py-3.5 font-semibold">Nilai {{ $observationTabs[$activeObservationTab] }}</th>
                                        <th class="px-3 py-3.5 font-semibold">Satuan</th>
                                        <th class="px-3 py-3.5 font-semibold">Praktisi</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Praktisi</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Observation</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Observation</th>
                                    @elseif ($isProcedure)
                                        <th class="px-3 py-3.5 font-semibold">No.</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Registrasi</th>
                                        <th class="px-3 py-3.5 font-semibold">No Rawat</th>
                                        <th class="px-3 py-3.5 font-semibold">No. RM</th>
                                        <th class="px-3 py-3.5 font-semibold">Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Periksa</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Lanjut</th>
                                        <th class="px-3 py-3.5 font-semibold">Pulang</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Encounter</th>
                                        <th class="px-3 py-3.5 font-semibold">Kode ICD-9</th>
                                        <th class="px-3 py-3.5 font-semibold">Prosedur</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Procedure</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Procedure</th>
                                    @elseif ($isClinicalImpression)
                                        <th class="px-3 py-3.5 font-semibold">No.</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Pemeriksaan</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Registrasi</th>
                                        <th class="px-3 py-3.5 font-semibold">No Rawat</th>
                                        <th class="px-3 py-3.5 font-semibold">No. RM</th>
                                        <th class="px-3 py-3.5 font-semibold">Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Periksa</th>
                                        <th class="px-3 py-3.5 font-semibold">Pulang</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Encounter</th>
                                        <th class="px-3 py-3.5 font-semibold">Praktisi</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Praktisi</th>
                                        <th class="px-3 py-3.5 font-semibold">Penilaian</th>
                                        <th class="px-3 py-3.5 font-semibold">Keluhan</th>
                                        <th class="px-3 py-3.5 font-semibold">Pemeriksaan</th>
                                        <th class="px-3 py-3.5 font-semibold">Kode Penyakit</th>
                                        <th class="px-3 py-3.5 font-semibold">Penyakit</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Condition</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Clinical Impression</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Clinical Impression</th>
                                    @elseif ($isMedicationRequest)
                                        <th class="px-3 py-3.5 font-semibold">No.</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Registrasi</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Resep</th>
                                        <th class="px-3 py-3.5 font-semibold">No Rawat</th>
                                        <th class="px-3 py-3.5 font-semibold">No. RM</th>
                                        <th class="px-3 py-3.5 font-semibold">Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">Praktisi</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Praktisi</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Encounter</th>
                                        <th class="px-3 py-3.5 font-semibold">Layanan</th>
                                        <th class="px-3 py-3.5 font-semibold">Jenis Resep</th>
                                        <th class="px-3 py-3.5 font-semibold">No. Resep</th>
                                        <th class="px-3 py-3.5 font-semibold">No. Racik</th>
                                        <th class="px-3 py-3.5 font-semibold">Kode Barang</th>
                                        <th class="px-3 py-3.5 font-semibold">Nama Obat</th>
                                        <th class="px-3 py-3.5 font-semibold">Jumlah</th>
                                        <th class="px-3 py-3.5 font-semibold">Aturan Pakai</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Medication</th>
                                        <th class="px-3 py-3.5 font-semibold">Kode Obat</th>
                                        <th class="px-3 py-3.5 font-semibold">Bentuk Obat</th>
                                        <th class="px-3 py-3.5 font-semibold">Rute</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Medication Request</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Medication Request</th>
                                    @elseif ($isMedicationDispense)
                                        <th class="px-3 py-3.5 font-semibold">No.</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Pemberian</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Resep</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Registrasi</th>
                                        <th class="px-3 py-3.5 font-semibold">No Rawat</th>
                                        <th class="px-3 py-3.5 font-semibold">No. RM</th>
                                        <th class="px-3 py-3.5 font-semibold">Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">Praktisi</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Praktisi</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Encounter</th>
                                        <th class="px-3 py-3.5 font-semibold">Layanan</th>
                                        <th class="px-3 py-3.5 font-semibold">No. Resep</th>
                                        <th class="px-3 py-3.5 font-semibold">Kode Barang</th>
                                        <th class="px-3 py-3.5 font-semibold">Nama Obat</th>
                                        <th class="px-3 py-3.5 font-semibold">Jumlah</th>
                                        <th class="px-3 py-3.5 font-semibold">Aturan Pakai</th>
                                        <th class="px-3 py-3.5 font-semibold">No. Batch</th>
                                        <th class="px-3 py-3.5 font-semibold">No. Faktur</th>
                                        <th class="px-3 py-3.5 font-semibold">Depo Farmasi</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Lokasi</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Medication</th>
                                        <th class="px-3 py-3.5 font-semibold">Kode Obat</th>
                                        <th class="px-3 py-3.5 font-semibold">Bentuk Obat</th>
                                        <th class="px-3 py-3.5 font-semibold">Rute</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Medication Dispense</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Medication Dispense</th>
                                    @elseif ($isMedicationStatement)
                                        <th class="px-3 py-3.5 font-semibold">No.</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Registrasi</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Penyerahan</th>
                                        <th class="px-3 py-3.5 font-semibold">No Rawat</th>
                                        <th class="px-3 py-3.5 font-semibold">No. RM</th>
                                        <th class="px-3 py-3.5 font-semibold">Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">Praktisi</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Praktisi</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Encounter</th>
                                        <th class="px-3 py-3.5 font-semibold">Layanan</th>
                                        <th class="px-3 py-3.5 font-semibold">Jenis Resep</th>
                                        <th class="px-3 py-3.5 font-semibold">No. Resep</th>
                                        <th class="px-3 py-3.5 font-semibold">No. Racik</th>
                                        <th class="px-3 py-3.5 font-semibold">Kode Barang</th>
                                        <th class="px-3 py-3.5 font-semibold">Nama Obat</th>
                                        <th class="px-3 py-3.5 font-semibold">Jumlah</th>
                                        <th class="px-3 py-3.5 font-semibold">Aturan Pakai</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Medication</th>
                                        <th class="px-3 py-3.5 font-semibold">Kode Obat</th>
                                        <th class="px-3 py-3.5 font-semibold">Bentuk Obat</th>
                                        <th class="px-3 py-3.5 font-semibold">Rute</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Medication Statement</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Medication Statement</th>
                                    @elseif ($isCarePlan)
                                        <th class="px-3 py-3.5 font-semibold">No.</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Pemeriksaan</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Registrasi</th>
                                        <th class="px-3 py-3.5 font-semibold">No Rawat</th>
                                        <th class="px-3 py-3.5 font-semibold">No. RM</th>
                                        <th class="px-3 py-3.5 font-semibold">Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Encounter</th>
                                        <th class="px-3 py-3.5 font-semibold">Rencana Tindak Lanjut</th>
                                        <th class="px-3 py-3.5 font-semibold">Praktisi</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Praktisi</th>
                                        <th class="px-3 py-3.5 font-semibold">Layanan</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Care Plan</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Care Plan</th>
                                    @elseif ($isCondition)
                                        <th class="px-3 py-3.5 font-semibold">No.</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Registrasi</th>
                                        <th class="px-3 py-3.5 font-semibold">No Rawat</th>
                                        <th class="px-3 py-3.5 font-semibold">No. RM</th>
                                        <th class="px-3 py-3.5 font-semibold">Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Periksa</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Lanjut</th>
                                        <th class="px-3 py-3.5 font-semibold">Pulang</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Encounter</th>
                                        <th class="px-3 py-3.5 font-semibold">Kode Penyakit</th>
                                        <th class="px-3 py-3.5 font-semibold">Diagnosis</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Condition</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Condition</th>
                                    @else
                                        <th class="px-3 py-3.5 text-center font-semibold">No.</th>
                                        <th class="px-3 py-3.5 font-semibold">Waktu Registrasi</th>
                                        <th class="px-3 py-3.5 font-semibold">No Rawat</th>
                                        <th class="px-3 py-3.5 font-semibold">No. RM</th>
                                        <th class="px-3 py-3.5 font-semibold">Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Pasien</th>
                                        <th class="px-3 py-3.5 font-semibold">Kode Dokter</th>
                                        <th class="px-3 py-3.5 font-semibold">Dokter</th>
                                        <th class="px-3 py-3.5 font-semibold">NIK Dokter</th>
                                        <th class="px-3 py-3.5 font-semibold">Kode Poli</th>
                                        <th class="px-3 py-3.5 font-semibold">Poli</th>
                                        <th class="px-3 py-3.5 font-semibold">Jenis Layanan</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Lokasi</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Periksa</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Lanjut</th>
                                        <th class="px-3 py-3.5 font-semibold">Pulang</th>
                                        <th class="px-3 py-3.5 font-semibold">ID Encounter</th>
                                        <th class="px-3 py-3.5 font-semibold">Status Encounter</th>
                                    @endif
                                    </tr>
                                </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse ($rows as $index => $row)
                                            <tr class="hover:bg-slate-50/80">
                                            @if ($isObservation)
                                                <td class="px-3 py-3.5 text-slate-500">{{ $index + 1 }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_pemeriksaan'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_registrasi'] }}</td>
                                                <td class="px-3 py-3.5 font-medium text-slate-900">{{ $row['no_rawat'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['norm'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['stts'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['jenis_layanan'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['pulang'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_encounter'] }}</td>
                                                <td class="px-3 py-3.5 font-semibold text-slate-900">{{ $row['nilai'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['satuan'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['praktisi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_praktisi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_observation'] }}</td>
                                                <td class="px-3 py-3.5">
                                                    <span class="@if($row['warna'] === 'emerald') bg-emerald-50 text-emerald-700 @elseif($row['warna'] === 'rose') bg-rose-50 text-rose-700 @else bg-amber-50 text-amber-700 @endif inline-flex rounded-full px-3 py-1 text-[11px] font-semibold">
                                                        {{ $row['status_observation'] }}
                                                    </span>
                                                </td>
                                            @elseif ($isProcedure)
                                                <td class="px-3 py-3.5 text-slate-500">{{ $index + 1 }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_registrasi'] }}</td>
                                                <td class="px-3 py-3.5 font-medium text-slate-900">{{ $row['no_rawat'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['norm'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['stts'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['status_lanjut'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['pulang'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_encounter'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['kode'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['prosedur'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_procedure'] }}</td>
                                                <td class="px-3 py-3.5">
                                                    <span class="@if($row['warna'] === 'emerald') bg-emerald-50 text-emerald-700 @elseif($row['warna'] === 'rose') bg-rose-50 text-rose-700 @else bg-amber-50 text-amber-700 @endif inline-flex rounded-full px-3 py-1 text-[11px] font-semibold">
                                                        {{ $row['status_procedure'] }}
                                                    </span>
                                                </td>
                                            @elseif ($isClinicalImpression)
                                                <td class="px-3 py-3.5 text-slate-500">{{ $index + 1 }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_pemeriksaan'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_registrasi'] }}</td>
                                                <td class="px-3 py-3.5 font-medium text-slate-900">{{ $row['no_rawat'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['norm'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['stts'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['pulang'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_encounter'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['praktisi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_praktisi'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 text-slate-700">{{ $row['penilaian'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 text-slate-700">{{ $row['keluhan'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 text-slate-700">{{ $row['pemeriksaan'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['kd_penyakit'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 text-slate-700">{{ $row['penyakit'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_condition'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_clinical_impression'] }}</td>
                                                <td class="px-3 py-3.5">
                                                    <span class="@if($row['warna'] === 'emerald') bg-emerald-50 text-emerald-700 @elseif($row['warna'] === 'rose') bg-rose-50 text-rose-700 @else bg-amber-50 text-amber-700 @endif inline-flex rounded-full px-3 py-1 text-[11px] font-semibold">
                                                        {{ $row['status_clinical_impression'] }}
                                                    </span>
                                                </td>
                                            @elseif ($isMedicationRequest)
                                                <td class="px-3 py-3.5 text-slate-500">{{ $index + 1 }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_registrasi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_resep'] }}</td>
                                                <td class="px-3 py-3.5 font-medium text-slate-900">{{ $row['no_rawat'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['norm'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['praktisi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_praktisi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_encounter'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['jenis_layanan'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['jenis_resep'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['no_resep'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['no_racik'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['kode_brng'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['obat_display'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['jumlah'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 text-slate-700">{{ $row['aturan_pakai'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_medication'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['obat_code'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['form_display'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['route_display'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_medicationrequest'] }}</td>
                                                <td class="px-3 py-3.5">
                                                    <span class="@if($row['warna'] === 'emerald') bg-emerald-50 text-emerald-700 @elseif($row['warna'] === 'rose') bg-rose-50 text-rose-700 @else bg-amber-50 text-amber-700 @endif inline-flex rounded-full px-3 py-1 text-[11px] font-semibold">
                                                        {{ $row['status_medicationrequest'] }}
                                                    </span>
                                                </td>
                                            @elseif ($isMedicationDispense)
                                                <td class="px-3 py-3.5 text-slate-500">{{ $index + 1 }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_pemberian'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_resep'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_registrasi'] }}</td>
                                                <td class="px-3 py-3.5 font-medium text-slate-900">{{ $row['no_rawat'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['norm'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['praktisi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_praktisi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_encounter'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['jenis_layanan'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['no_resep'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['kode_brng'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['obat_display'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['jumlah'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 text-slate-700">{{ $row['aturan_pakai'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['no_batch'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['no_faktur'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 text-slate-700">{{ $row['depo'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_lokasi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_medication'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['obat_code'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['form_display'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['route_display'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_medicationdispense'] }}</td>
                                                <td class="px-3 py-3.5">
                                                    <span class="@if($row['warna'] === 'emerald') bg-emerald-50 text-emerald-700 @elseif($row['warna'] === 'rose') bg-rose-50 text-rose-700 @else bg-amber-50 text-amber-700 @endif inline-flex rounded-full px-3 py-1 text-[11px] font-semibold">
                                                        {{ $row['status_medicationdispense'] }}
                                                    </span>
                                                </td>
                                            @elseif ($isMedicationStatement)
                                                <td class="px-3 py-3.5 text-slate-500">{{ $index + 1 }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_registrasi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_penyerahan'] }}</td>
                                                <td class="px-3 py-3.5 font-medium text-slate-900">{{ $row['no_rawat'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['norm'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['praktisi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_praktisi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_encounter'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['jenis_layanan'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['jenis_resep'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['no_resep'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['no_racik'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['kode_brng'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['obat_display'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['jumlah'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 text-slate-700">{{ $row['aturan_pakai'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_medication'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['obat_code'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['form_display'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['route_display'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_medicationstatement'] }}</td>
                                                <td class="px-3 py-3.5">
                                                    <span class="@if($row['warna'] === 'emerald') bg-emerald-50 text-emerald-700 @elseif($row['warna'] === 'rose') bg-rose-50 text-rose-700 @else bg-amber-50 text-amber-700 @endif inline-flex rounded-full px-3 py-1 text-[11px] font-semibold">
                                                        {{ $row['status_medicationstatement'] }}
                                                    </span>
                                                </td>
                                            @elseif ($isCarePlan)
                                                <td class="px-3 py-3.5 text-slate-500">{{ $index + 1 }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_pemeriksaan'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_registrasi'] }}</td>
                                                <td class="px-3 py-3.5 font-medium text-slate-900">{{ $row['no_rawat'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['norm'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_encounter'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 text-slate-700">{{ $row['rtl'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['praktisi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_praktisi'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['jenis_layanan'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_careplan'] }}</td>
                                                <td class="px-3 py-3.5">
                                                    <span class="@if($row['warna'] === 'emerald') bg-emerald-50 text-emerald-700 @elseif($row['warna'] === 'rose') bg-rose-50 text-rose-700 @else bg-amber-50 text-amber-700 @endif inline-flex rounded-full px-3 py-1 text-[11px] font-semibold">
                                                        {{ $row['status_careplan'] }}
                                                    </span>
                                                </td>
                                            @elseif ($isCondition)
                                                <td class="px-3 py-3.5 text-slate-500">{{ $index + 1 }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_registrasi'] }}</td>
                                                <td class="px-3 py-3.5 font-medium text-slate-900">{{ $row['no_rawat'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['norm'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['stts'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['status_lanjut'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['pulang'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_encounter'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['kd_penyakit'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['penyakit'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_condition'] }}</td>
                                                <td class="px-3 py-3.5">
                                                    <span class="@if($row['warna'] === 'emerald') bg-emerald-50 text-emerald-700 @elseif($row['warna'] === 'rose') bg-rose-50 text-rose-700 @else bg-amber-50 text-amber-700 @endif inline-flex rounded-full px-3 py-1 text-[11px] font-semibold">
                                                        {{ $row['status_condition'] }}
                                                    </span>
                                                </td>
                                            @else
                                                <td class="px-3 py-3.5 text-center text-slate-500">{{ $index + 1 }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['waktu_registrasi'] }}</td>
                                                <td class="px-3 py-3.5 font-medium text-slate-900">{{ $row['no_rawat'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['norm'] }}</td>
                                                <td class="patient-name-cell px-3 py-3.5 font-medium text-slate-900">{{ $row['pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_pasien'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['kd_dokter'] }}</td>
                                                <td class="px-3 py-3.5 font-medium text-slate-900">{{ $row['dokter'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['nik_dokter'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['kd_poli'] }}</td>
                                                <td class="px-3 py-3.5 font-medium text-slate-900">{{ $row['poli'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['jenis_layanan'] }}</td>
                                                <td class="px-3 py-3.5 font-medium text-slate-900">{{ $row['id_lokasi_satusehat'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['stts'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-600">{{ $row['status_lanjut'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['pulang'] }}</td>
                                                <td class="px-3 py-3.5 text-slate-700">{{ $row['id_encounter'] }}</td>
                                                <td class="px-3 py-3.5">
                                                    <span class="@if($row['warna'] === 'emerald') bg-emerald-50 text-emerald-700 @elseif($row['warna'] === 'rose') bg-rose-50 text-rose-700 @else bg-amber-50 text-amber-700 @endif inline-flex rounded-full px-3 py-1 text-[11px] font-semibold">
                                                        {{ $row['status_encounter'] }}
                                                    </span>
                                                </td>
                                            @endif
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ $isObservation ? 17 : ($isClinicalImpression ? 20 : ($isMedicationDispense ? 27 : (($isMedicationRequest || $isMedicationStatement) ? 24 : (($isCondition || $isProcedure || $isCarePlan) ? 14 : 18)))) }}" class="px-3 py-8 text-center text-slate-500">
                                                    @if ($isObservation)
                                                        Tidak ada data {{ $observationTabs[$activeObservationTab] }} pada filter yang dipilih.
                                                    @else
                                                        Tidak ada data {{ strtolower($pageTitle) }} pada filter yang dipilih.
                                                    @endif
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
