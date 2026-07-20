@php
    $sidebarGroups = [
        ['label' => 'Monitor BPJS', 'color' => 'text-blue-300', 'items' => [
            ['Statistik Pasien', route('monitoring.statistik-pasien')], ['Bridging Pelayanan', url('/')],
            ['Belum Bridging', route('monitoring.bpjs-tidak-bridging')], ['Kendala Bridging', route('monitoring.kendala-bridging')],
            ['Timeline Pelayanan', route('monitoring.timeline-pelayanan')],
        ]],
        ['label' => 'Satu Sehat', 'color' => 'text-emerald-300', 'items' => [
            ['Encounter', route('satu-sehat.encounter')], ['Condition', route('satu-sehat.condition')],
            ['Observation', route('satu-sehat.observation')], ['Procedure', route('satu-sehat.procedure')],
            ['Clinical Impression', route('satu-sehat.clinical-impression')], ['Medication Request', route('satu-sehat.medication-request')],
            ['Medication Dispense', route('satu-sehat.medication-dispense')], ['Medication Statement', route('satu-sehat.medication-statement')],
            ['Care Plan', route('satu-sehat.care-plan')],
        ]],
        ['label' => 'Analitik', 'color' => 'text-sky-300', 'items' => array_map(fn($item) => [$item, '#'], ['Capaian Bridging','SLA Pelayanan','Waktu Tunggu','Analisis Rujukan','Analisis TACC','Statistik Dokter','Statistik Poli','Grafik Capaian'])],
        ['label' => 'Laporan', 'color' => 'text-emerald-300', 'items' => array_map(fn($item) => [$item, '#'], ['Rekap Harian','Rekap Bulanan','Rekap Antrean BPJS','Rekap Bridging Pelayanan','Rekap Kendala Bridging','Rekap Waktu Pelayanan','Audit Bridging','Export Data'])],
        ['label' => 'Audit Log', 'color' => 'text-amber-300', 'items' => array_map(fn($item) => [$item, '#'], ['Log Antrean BPJS','Log Bridging Pelayanan','Log Request API','Log Response API','Riwayat Retry Bridging','Aktivitas Pengguna'])],
        ['label' => 'Master Data', 'color' => 'text-violet-300', 'items' => array_map(fn($item) => [$item, '#'], ['Mapping Dokter BPJS','Mapping Poli BPJS','Mapping Diagnosa (ICD-10)','Mapping Tindakan','Mapping Status Pulang','Jadwal Dokter'])],
        ['label' => 'Pengaturan', 'color' => 'text-slate-300', 'items' => array_map(fn($item) => [$item, '#'], ['Target SLA Pelayanan','Konfigurasi Bridging','Retry Bridging Otomatis','Notifikasi Monitoring','Hak Akses Pengguna','Parameter Sistem'])],
    ];
@endphp
@foreach($sidebarGroups as $group)
    <section>
        <button type="button" class="flex w-full cursor-pointer items-center gap-2 rounded-2xl px-0 py-1.5 text-left text-white" data-dropdown-toggle aria-expanded="false">
            <span class="flex h-11 w-11 items-center justify-center {{ $group['color'] }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="M8 12h8M12 8v8"/></svg>
            </span>
            <span class="min-w-0 flex-1 text-sm font-semibold">{{ $group['label'] }}</span>
            <div class="ml-auto flex shrink-0 items-center gap-2"><div class="h-px w-10 bg-white/10"></div><svg class="h-5 w-5 text-slate-400 transition" data-dropdown-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></div>
        </button>
        <div class="hidden space-y-1 pl-[7px]" data-dropdown-panel>
            @foreach($group['items'] as [$label, $href])<a href="{{ $href }}" class="flex items-center gap-3 rounded-xl px-2 py-2.5 text-sm text-slate-300 hover:bg-white/5"><span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span><span>{{ $label }}</span></a>@endforeach
        </div>
    </section>
@endforeach
