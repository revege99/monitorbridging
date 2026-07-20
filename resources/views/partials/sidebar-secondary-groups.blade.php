@php
    $secondaryGroups = [
        ['label' => 'Analitik', 'color' => 'text-sky-300', 'items' => ['Capaian Bridging','SLA Pelayanan','Waktu Tunggu','Analisis Rujukan','Analisis TACC','Statistik Dokter','Statistik Poli','Grafik Capaian']],
        ['label' => 'Laporan', 'color' => 'text-emerald-300', 'items' => ['Rekap Harian','Rekap Bulanan','Rekap Antrean BPJS','Rekap Bridging Pelayanan','Rekap Kendala Bridging','Rekap Waktu Pelayanan','Audit Bridging','Export Data']],
        ['label' => 'Audit Log', 'color' => 'text-amber-300', 'items' => ['Log Antrean BPJS','Log Bridging Pelayanan','Log Request API','Log Response API','Riwayat Retry Bridging','Aktivitas Pengguna']],
        ['label' => 'Master Data', 'color' => 'text-violet-300', 'items' => ['Mapping Dokter BPJS','Mapping Poli BPJS','Mapping Diagnosa (ICD-10)','Mapping Tindakan','Mapping Status Pulang','Jadwal Dokter']],
        ['label' => 'Pengaturan', 'color' => 'text-slate-300', 'items' => ['Target SLA Pelayanan','Konfigurasi Bridging','Retry Bridging Otomatis','Notifikasi Monitoring','Hak Akses Pengguna','Parameter Sistem']],
    ];
@endphp
@foreach($secondaryGroups as $group)
    <section>
        <div class="rounded-2xl px-0">
            <button type="button" class="flex w-full cursor-pointer items-center gap-2.5 rounded-2xl px-0 py-1.5 text-left text-white" data-dropdown-toggle aria-expanded="false">
                <span class="flex h-11 w-11 items-center justify-center {{ $group['color'] }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V9M10 19V5M16 19v-7M22 19V3"/></svg>
                </span>
                <span class="min-w-0 flex-1 text-sm font-semibold">{{ $group['label'] }}</span>
                <div class="ml-auto flex shrink-0 items-center gap-2"><div class="h-px w-10 bg-white/10"></div><svg class="h-5 w-5 text-slate-400 transition duration-200" data-dropdown-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg></div>
            </button>
            <div class="mt-1 hidden space-y-1 pl-[7px]" data-dropdown-panel>
                @foreach($group['items'] as $item)<a href="#" class="flex items-center gap-3 rounded-xl px-2 py-2.5 text-sm text-slate-300 hover:bg-white/5"><span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span><span>{{ $item }}</span></a>@endforeach
            </div>
        </div>
    </section>
@endforeach
