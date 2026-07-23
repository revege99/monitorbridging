<?php

namespace App\Http\Controllers;

use App\Models\ServiceRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now('Asia/Jakarta')->toDateString();
        $clinicId = $request->user()->isSuperadmin()
            ? $request->session()->get('active_clinic_id')
            : $request->user()->clinic_id;

        $metrics = [
            'registrations' => 0,
            'bpjs' => 0,
            'bridged' => 0,
            'not_bridged' => 0,
        ];
        $poliRows = collect();
        $recentPatients = collect();
        $dbError = null;

        try {
            $activeRegistrations = DB::table('reg_periksa as rp')
                ->whereDate('rp.tgl_registrasi', $today)
                ->where('rp.stts', '<>', 'Batal');

            $bpjsRegistrations = (clone $activeRegistrations)->where('rp.kd_pj', 'BPJ');
            $hasRegistration = 'EXISTS (SELECT 1 FROM pcare_pendaftaran pp WHERE pp.no_rawat = rp.no_rawat)';
            $hasVisit = 'EXISTS (SELECT 1 FROM pcare_kunjungan_umum pk WHERE pk.no_rawat = rp.no_rawat)';

            $metrics = [
                'registrations' => (clone $activeRegistrations)->count(),
                'bpjs' => (clone $bpjsRegistrations)->count(),
                'bridged' => (clone $bpjsRegistrations)->whereRaw($hasRegistration)->whereRaw($hasVisit)->count(),
                'not_bridged' => (clone $bpjsRegistrations)->whereRaw("NOT ($hasRegistration AND $hasVisit)")->count(),
            ];

            $poliRows = DB::table('reg_periksa as rp')
                ->join('poliklinik as pl', 'pl.kd_poli', '=', 'rp.kd_poli')
                ->whereDate('rp.tgl_registrasi', $today)
                ->where('rp.stts', '<>', 'Batal')
                ->selectRaw('pl.nm_poli, COUNT(*) AS total')
                ->groupBy('pl.nm_poli')
                ->orderByDesc('total')
                ->limit(6)
                ->get();

            $recentPatients = DB::table('reg_periksa as rp')
                ->leftJoin('pasien as ps', 'ps.no_rkm_medis', '=', 'rp.no_rkm_medis')
                ->leftJoin('poliklinik as pl', 'pl.kd_poli', '=', 'rp.kd_poli')
                ->whereDate('rp.tgl_registrasi', $today)
                ->select(['rp.no_rawat', 'rp.no_reg', 'rp.jam_reg', 'rp.stts', 'rp.kd_pj', 'ps.nm_pasien', 'pl.nm_poli'])
                ->orderByDesc('rp.jam_reg')
                ->limit(7)
                ->get();
        } catch (Throwable $exception) {
            report($exception);
            $dbError = 'Data klinik belum dapat dimuat. Periksa kembali koneksi database klinik.';
        }

        $serviceRun = $clinicId
            ? ServiceRun::query()
                ->where('clinic_id', $clinicId)
                ->where('service_name', 'antrean_fktp_add')
                ->latest('id')
                ->first()
            : null;

        $serviceOnline = $serviceRun?->status === 'running'
            && $serviceRun->last_heartbeat_at?->greaterThan(now()->subSeconds(30));

        return view('dashboard', [
            'metrics' => $metrics,
            'poliRows' => $poliRows,
            'recentPatients' => $recentPatients,
            'serviceRun' => $serviceRun,
            'serviceOnline' => $serviceOnline,
            'dbError' => $dbError,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }
}
