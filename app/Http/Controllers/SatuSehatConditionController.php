<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SatuSehatConditionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $dbError = null;

        try {
            $rows = $this->conditionRows($filters);
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
            $rows = collect();
        }

        return view('satu-sehat-encounter', [
            'resourceType' => 'condition',
            'rows' => $rows,
            'dbError' => $dbError,
            'usingFallback' => $dbError !== null,
            'filters' => $filters,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    private function filters(Request $request): array
    {
        $today = now()->toDateString();

        return [
            'search' => trim((string) $request->query('search', '')),
            'start_date' => $request->query('start_date', $today) ?: $today,
            'end_date' => $request->query('end_date', $today) ?: $today,
        ];
    }

    private function conditionRows(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->orderByDesc('reg_periksa.tgl_registrasi')
            ->orderByDesc('reg_periksa.jam_reg')
            ->limit(500)
            ->get()
            ->map(fn ($row) => [
                'waktu_registrasi' => $row->tgl_registrasi . 'T' . $row->jam_reg . '+07:00',
                'no_rawat' => $row->no_rawat,
                'norm' => $row->no_rkm_medis,
                'pasien' => $row->nm_pasien ?: '-',
                'nik_pasien' => $row->no_ktp ?: '-',
                'stts' => $row->stts ?: '-',
                'status_lanjut' => $row->status_lanjut ?: '-',
                'pulang' => $row->pulang ?: '-',
                'id_encounter' => $row->id_encounter ?: '-',
                'kd_penyakit' => $row->kd_penyakit ?: '-',
                'penyakit' => $row->nm_penyakit ?: '-',
                'id_condition' => $row->id_condition ?: '-',
                'status_condition' => match (true) {
                    $row->status_condition === 'berhasil' && filled($row->id_condition) => 'Sudah Terkirim',
                    $row->status_condition === 'gagal' => 'Gagal',
                    default => 'Belum Terkirim',
                },
                'warna' => match (true) {
                    $row->status_condition === 'berhasil' && filled($row->id_condition) => 'emerald',
                    $row->status_condition === 'gagal' => 'rose',
                    default => 'amber',
                },
            ]);
    }

    private function baseQuery(array $filters): Builder
    {
        $query = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('diagnosa_pasien', 'diagnosa_pasien.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('penyakit', 'diagnosa_pasien.kd_penyakit', '=', 'penyakit.kd_penyakit')
            ->leftJoin('satu_sehat_encounter_new', 'satu_sehat_encounter_new.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('nota_jalan', 'nota_jalan.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('nota_inap', 'nota_inap.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('satu_sehat_condition_new', function ($join) {
                $join->on('satu_sehat_condition_new.no_rawat', '=', 'diagnosa_pasien.no_rawat')
                    ->on('satu_sehat_condition_new.kd_penyakit', '=', 'diagnosa_pasien.kd_penyakit');
            })
            ->where(function ($query) use ($filters) {
                $query->whereBetween('reg_periksa.tgl_registrasi', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('satu_sehat_condition_new.created_at', [
                        $filters['start_date'] . ' 00:00:00',
                        $filters['end_date'] . ' 23:59:59',
                    ]);
            })
            ->select([
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.no_ktp',
                'reg_periksa.stts',
                'reg_periksa.status_lanjut',
                DB::raw("
                    CASE
                        WHEN nota_inap.tanggal IS NOT NULL THEN CONCAT(nota_inap.tanggal, 'T', nota_inap.jam, '+07:00')
                        WHEN nota_jalan.tanggal IS NOT NULL THEN CONCAT(nota_jalan.tanggal, 'T', nota_jalan.jam, '+07:00')
                        ELSE ''
                    END as pulang
                "),
                'satu_sehat_encounter_new.id_encounter',
                'diagnosa_pasien.kd_penyakit',
                'penyakit.nm_penyakit',
                DB::raw("IFNULL(satu_sehat_condition_new.id_condition, '') as id_condition"),
                DB::raw('satu_sehat_condition_new.status as status_condition'),
            ]);

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('reg_periksa.no_rawat', 'like', $search)
                    ->orWhere('reg_periksa.no_rkm_medis', 'like', $search)
                    ->orWhere('pasien.nm_pasien', 'like', $search)
                    ->orWhere('pasien.no_ktp', 'like', $search)
                    ->orWhere('diagnosa_pasien.kd_penyakit', 'like', $search)
                    ->orWhere('penyakit.nm_penyakit', 'like', $search)
                    ->orWhere('reg_periksa.stts', 'like', $search)
                    ->orWhere('reg_periksa.status_lanjut', 'like', $search);
            });
        }

        return $query;
    }
}
