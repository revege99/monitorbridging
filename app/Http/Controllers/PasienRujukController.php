<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class PasienRujukController extends Controller
{
    public function index(Request $request)
    {
        $dbError = null;
        $usingFallback = false;
        $filters = $this->buildFilters($request);

        try {
            $rujukRows = $this->getRujukRowsFromDatabase($filters);
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
            $usingFallback = true;
            $rujukRows = $this->fallbackRows();
        }

        $summary = $this->buildSummary($rujukRows);

        return view('pasien-rujuk', [
            'rujukRows' => $rujukRows,
            'summary' => $summary,
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
            'search' => trim((string) $request->query('search', '')),
            'start_date' => $request->query('start_date', $today) ?: $today,
            'end_date' => $request->query('end_date', $today) ?: $today,
        ];
    }

    private function getRujukRowsFromDatabase(array $filters): Collection
    {
        $rows = DB::table('reg_periksa as rp')
            ->join('pasien as ps', 'ps.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->leftJoin('poliklinik as pl', 'pl.kd_poli', '=', 'rp.kd_poli')
            ->join('pcare_rujuk_subspesialis as prs', 'prs.no_rawat', '=', 'rp.no_rawat')
            ->leftJoin('diagnosa_pasien as dp', function ($join) {
                $join->on('dp.no_rawat', '=', 'rp.no_rawat')
                    ->where('dp.prioritas', '=', 1)
                    ->where('dp.status', '=', 'Ralan');
            })
            ->leftJoin('penyakit as p', 'p.kd_penyakit', '=', 'dp.kd_penyakit')
            ->select([
                'rp.no_rawat',
                'rp.no_rkm_medis',
                'rp.tgl_registrasi',
                'rp.jam_reg',
                'rp.kd_poli',
                'ps.nm_pasien',
                'pl.nm_poli',
                'prs.tglDaftar as tgl_rujuk',
                'prs.nmSubSpesialis',
                'prs.nmPPK',
                'prs.kdTACC',
                'prs.nmTACC',
                'prs.alasanTACC',
                'prs.nmDiag1 as diag_rujuk',
                'prs.tglEstRujuk',
                'dp.kd_penyakit',
                'dp.nonSpesialis',
                'p.nm_penyakit',
            ])
            ->where('rp.kd_pj', 'BPJ')
            ->where('rp.stts', '!=', 'Batal');

        if ($filters['start_date']) {
            $rows->whereDate('rp.tgl_registrasi', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $rows->whereDate('rp.tgl_registrasi', '<=', $filters['end_date']);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $rows->where(function ($query) use ($search) {
                $query->where('rp.no_rawat', 'like', '%' . $search . '%')
                    ->orWhere('rp.no_rkm_medis', 'like', '%' . $search . '%')
                    ->orWhere('ps.nm_pasien', 'like', '%' . $search . '%')
                    ->orWhere('dp.kd_penyakit', 'like', '%' . $search . '%')
                    ->orWhere('p.nm_penyakit', 'like', '%' . $search . '%')
                    ->orWhere('dp.nonSpesialis', 'like', '%' . $search . '%');
            });
        }

        return $rows
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
                    'tgl_rujuk' => $row->tgl_rujuk,
                    'tgl_estimasi_rujuk' => $row->tglEstRujuk,
                    'subspesialis' => $row->nmSubSpesialis ?: '-',
                    'ppk_rujuk' => $row->nmPPK ?: '-',
                    'diagnosa' => $row->nm_penyakit ?: $row->diag_rujuk ?: '-',
                    'kode_diagnosa' => $row->kd_penyakit ?: '-',
                    'non_spesialis' => $row->nonSpesialis ?: '-',
                    'tacc' => $row->nmTACC ?: '-',
                    'alasan_tacc' => $row->alasanTACC ?: '-',
                ];
            });
    }

    private function fallbackRows(): Collection
    {
        return collect([
            [
                'no_rawat' => '2026/07/15/000001',
                'norm' => '000145',
                'pasien' => 'SITI AMINAH',
                'poli' => 'POLI UMUM',
                'reg_tanggal' => '2026-07-15',
                'reg_jam' => '07:12:08',
                'tgl_rujuk' => '2026-07-15',
                'tgl_estimasi_rujuk' => '2026-07-16',
                'subspesialis' => 'PARU',
                'ppk_rujuk' => 'RSU Contoh',
                'diagnosa' => 'Bacterial pneumonia, unspecified',
                'kode_diagnosa' => 'J18.9',
                'non_spesialis' => 'Bacterial pneumonia, unspecified',
                'tacc' => 'Time',
                'alasan_tacc' => 'Perlu observasi lanjutan.',
            ],
        ]);
    }

    private function buildSummary(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            'non_spesialis' => $rows->filter(fn ($row) => $row['non_spesialis'] !== '-' && $row['non_spesialis'] !== 'false')->count(),
            'dengan_tacc' => $rows->filter(fn ($row) => $row['tacc'] !== '-')->count(),
            'tanpa_tacc' => $rows->filter(fn ($row) => $row['tacc'] === '-')->count(),
        ];
    }
}
