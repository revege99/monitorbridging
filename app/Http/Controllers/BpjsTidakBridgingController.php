<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class BpjsTidakBridgingController extends Controller
{
    public function index(Request $request)
    {
        $dbError = null;
        $usingFallback = false;
        $filters = $this->buildFilters($request);

        try {
            $rows = $this->getRowsFromDatabase($filters);
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
            $usingFallback = true;
            $rows = $this->fallbackRows();
        }

        return view('bpjs-tidak-bridging', [
            'nonSpesialisRows' => $rows,
            'summary' => $this->buildSummary($rows),
            'dbError' => $dbError,
            'usingFallback' => $usingFallback,
            'filters' => $filters,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    private function buildFilters(Request $request): array
    {
        $today = now()->toDateString();

        return [
            'start_date' => $request->query('start_date', $today) ?: $today,
            'end_date' => $request->query('end_date', $today) ?: $today,
        ];
    }

    private function getRowsFromDatabase(array $filters): Collection
    {
        return DB::table('reg_periksa as rp')
            ->join('pasien as ps', 'ps.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->leftJoin('poliklinik as pl', 'pl.kd_poli', '=', 'rp.kd_poli')
            ->join('pcare_rujuk_subspesialis as prs', 'prs.no_rawat', '=', 'rp.no_rawat')
            ->leftJoin('diagnosa_pasien as dp', function ($join) {
                $join->on('dp.no_rawat', '=', 'rp.no_rawat')
                    ->where('dp.prioritas', '=', 1)
                    ->where('dp.status', '=', 'Ralan');
            })
            ->leftJoin('penyakit as py', 'py.kd_penyakit', '=', 'dp.kd_penyakit')
            ->select([
                'rp.no_rawat',
                'rp.no_rkm_medis',
                'rp.tgl_registrasi',
                'rp.jam_reg',
                'ps.nm_pasien',
                'pl.nm_poli',
                'rp.kd_poli',
                'prs.tglDaftar as tgl_rujuk',
                'prs.tglEstRujuk',
                'prs.nmPPK',
                'prs.nmSubSpesialis',
                'prs.kdTACC',
                'prs.nmTACC',
                'prs.alasanTACC',
                'dp.kd_penyakit',
                'dp.nonSpesialis',
                'py.nm_penyakit',
            ])
            ->where('rp.kd_pj', 'BPJ')
            ->where('rp.stts', '!=', 'Batal')
            ->whereDate('rp.tgl_registrasi', '>=', $filters['start_date'])
            ->whereDate('rp.tgl_registrasi', '<=', $filters['end_date'])
            ->where(function ($query) {
                $query->where('dp.nonSpesialis', '=', '1')
                    ->orWhereRaw('LOWER(COALESCE(dp.nonSpesialis, "")) = ?', ['true']);
            })
            ->where(function ($query) {
                $query->whereNull('prs.kdTACC')
                    ->orWhere('prs.kdTACC', '=', '')
                    ->orWhere('prs.kdTACC', '=', '0')
                    ->orWhereNull('prs.nmTACC')
                    ->orWhere('prs.nmTACC', '=', '')
                    ->orWhere('prs.nmTACC', '=', '0');
            })
            ->orderByDesc('rp.tgl_registrasi')
            ->orderByDesc('rp.jam_reg')
            ->limit(100)
            ->get()
            ->map(function ($row) {
                return [
                    'no_rawat' => $row->no_rawat,
                    'norm' => $row->no_rkm_medis,
                    'pasien' => $row->nm_pasien ?: '-',
                    'poli' => $row->nm_poli ?: $row->kd_poli ?: '-',
                    'reg_tanggal' => $row->tgl_registrasi,
                    'reg_jam' => $row->jam_reg,
                    'tgl_rujuk' => $row->tgl_rujuk ?: '-',
                    'tgl_estimasi_rujuk' => $row->tglEstRujuk ?: '-',
                    'diagnosa' => $row->nm_penyakit ?: '-',
                    'kode_diagnosa' => $row->kd_penyakit ?: '-',
                    'non_spesialis' => $row->nonSpesialis ?: '-',
                    'tacc' => $row->nmTACC ?: ($row->kdTACC ?: '0'),
                    'alasan_tacc' => $row->alasanTACC ?: '-',
                    'ppk_rujuk' => $row->nmPPK ?: '-',
                    'subspesialis' => $row->nmSubSpesialis ?: '-',
                    'status_fraud' => 'Perlu TACC',
                ];
            });
    }

    private function fallbackRows(): Collection
    {
        return collect([
            [
                'no_rawat' => '2026/07/17/000011',
                'norm' => '000981',
                'pasien' => 'RINA KARTIKA',
                'poli' => 'POLI UMUM',
                'reg_tanggal' => '2026-07-17',
                'reg_jam' => '10:48:10',
                'tgl_rujuk' => '2026-07-17',
                'tgl_estimasi_rujuk' => '2026-07-18',
                'diagnosa' => 'Dyspepsia',
                'kode_diagnosa' => 'K30',
                'non_spesialis' => 'true',
                'tacc' => '0',
                'alasan_tacc' => '-',
                'ppk_rujuk' => 'RSU Contoh',
                'subspesialis' => 'PENYAKIT DALAM',
                'status_fraud' => 'Perlu TACC',
            ],
        ]);
    }

    private function buildSummary(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            'dengan_diagnosa' => $rows->filter(fn ($row) => $row['diagnosa'] !== '-')->count(),
            'tanpa_tacc' => $rows->filter(fn ($row) => in_array($row['tacc'], ['-', '', '0'], true))->count(),
            'dengan_estimasi' => $rows->filter(fn ($row) => $row['tgl_estimasi_rujuk'] !== '-')->count(),
        ];
    }
}
