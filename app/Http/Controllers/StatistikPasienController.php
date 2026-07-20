<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class StatistikPasienController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $filters = [
            'start_date' => $request->query('start_date', $today) ?: $today,
            'end_date' => $request->query('end_date', $today) ?: $today,
        ];

        $dbError = null;
        $usingFallback = false;

        try {
            $summary = $this->summaryQuery($filters)->first();
            $dailyRows = $this->dailyQuery($filters)->get();
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
            $usingFallback = true;
            $summary = (object) array_fill_keys([
                'total', 'rujuk', 'bpjs_batal', 'tidak_bridging', 'bridging_lengkap',
                'bridging_rujuk',
            ], 0);
            $dailyRows = collect();
        }

        return view('welcome', [
            'statisticsPage' => true,
            'pageTitle' => 'Statistik Pasien BPJS',
            'summary' => (array) $summary,
            'dailyRows' => $dailyRows,
            'dbError' => $dbError,
            'usingFallback' => $usingFallback,
            'filters' => $filters,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    private function baseQuery(array $filters): Builder
    {
        return DB::table('reg_periksa as rp')
            ->where('rp.kd_pj', 'BPJ')
            ->whereDate('rp.tgl_registrasi', '>=', $filters['start_date'])
            ->whereDate('rp.tgl_registrasi', '<=', $filters['end_date']);
    }

    private function summaryQuery(array $filters): Builder
    {
        return $this->baseQuery($filters)->selectRaw($this->statisticSelect());
    }

    private function dailyQuery(array $filters): Builder
    {
        return $this->baseQuery($filters)
            ->selectRaw('rp.tgl_registrasi')
            ->selectRaw($this->statisticSelect())
            ->groupBy('rp.tgl_registrasi')
            ->orderByDesc('rp.tgl_registrasi');
    }

    private function statisticSelect(): string
    {
        $hasRujuk = 'EXISTS (SELECT 1 FROM pcare_rujuk_subspesialis prs WHERE prs.no_rawat = rp.no_rawat)';
        $hasDaftar = 'EXISTS (SELECT 1 FROM pcare_pendaftaran pp WHERE pp.no_rawat = rp.no_rawat)';
        $hasKunjungan = 'EXISTS (SELECT 1 FROM pcare_kunjungan_umum pk WHERE pk.no_rawat = rp.no_rawat)';
        $active = "rp.stts <> 'Batal'";
        return implode(', ', [
            "SUM($active) as total",
            "SUM($active AND $hasRujuk) as rujuk",
            "SUM(rp.stts = 'Batal') as bpjs_batal",
            "SUM($active AND NOT $hasDaftar AND NOT $hasKunjungan AND NOT $hasRujuk) as tidak_bridging",
            "SUM($active AND $hasDaftar AND $hasKunjungan AND NOT $hasRujuk) as bridging_lengkap",
            "SUM($active AND $hasRujuk) as bridging_rujuk",
        ]);
    }
}
