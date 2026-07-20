<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SatuSehatProcedureController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'start_date' => $request->query('start_date', $today) ?: $today,
            'end_date' => $request->query('end_date', $today) ?: $today,
        ];
        $dbError = null;

        try {
            $rows = $this->procedureRows($filters);
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
            $rows = collect();
        }

        return view('satu-sehat-encounter', [
            'resourceType' => 'procedure',
            'rows' => $rows,
            'dbError' => $dbError,
            'usingFallback' => $dbError !== null,
            'filters' => $filters,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    private function procedureRows(array $filters): Collection
    {
        $query = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('prosedur_pasien', 'prosedur_pasien.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('icd9', 'icd9.kode', '=', 'prosedur_pasien.kode')
            ->leftJoin('satu_sehat_encounter_new', 'satu_sehat_encounter_new.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('nota_jalan', 'nota_jalan.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('nota_inap', 'nota_inap.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('satu_sehat_procedure_new', function ($join) {
                $join->on('satu_sehat_procedure_new.no_rawat', '=', 'prosedur_pasien.no_rawat')
                    ->on('satu_sehat_procedure_new.kode', '=', 'prosedur_pasien.kode');
            })
            ->where(function ($subQuery) use ($filters) {
                $subQuery->whereBetween('reg_periksa.tgl_registrasi', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('satu_sehat_procedure_new.created_at', [
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
                DB::raw("IFNULL(satu_sehat_encounter_new.id_encounter, '') as id_encounter"),
                'prosedur_pasien.kode',
                'icd9.deskripsi_panjang',
                DB::raw("IFNULL(satu_sehat_procedure_new.procedure_id, '') as procedure_id"),
                DB::raw('satu_sehat_procedure_new.status as procedure_status'),
            ]);

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('reg_periksa.no_rawat', 'like', $search)
                    ->orWhere('reg_periksa.no_rkm_medis', 'like', $search)
                    ->orWhere('pasien.nm_pasien', 'like', $search)
                    ->orWhere('pasien.no_ktp', 'like', $search)
                    ->orWhere('prosedur_pasien.kode', 'like', $search)
                    ->orWhere('icd9.deskripsi_panjang', 'like', $search)
                    ->orWhere('reg_periksa.stts', 'like', $search)
                    ->orWhere('reg_periksa.status_lanjut', 'like', $search);
            });
        }

        return $query
            ->orderByDesc('reg_periksa.tgl_registrasi')
            ->orderByDesc('reg_periksa.jam_reg')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                $sent = $row->procedure_status === 'berhasil' && filled($row->procedure_id);

                return [
                    'waktu_registrasi' => $row->tgl_registrasi . 'T' . $row->jam_reg . '+07:00',
                    'no_rawat' => $row->no_rawat,
                    'norm' => $row->no_rkm_medis,
                    'pasien' => $row->nm_pasien ?: '-',
                    'nik_pasien' => $row->no_ktp ?: '-',
                    'stts' => $row->stts ?: '-',
                    'status_lanjut' => $row->status_lanjut ?: '-',
                    'pulang' => $row->pulang ?: '-',
                    'id_encounter' => $row->id_encounter ?: '-',
                    'kode' => $row->kode ?: '-',
                    'prosedur' => $row->deskripsi_panjang ?: '-',
                    'id_procedure' => $row->procedure_id ?: '-',
                    'status_procedure' => match (true) {
                        $sent => 'Sudah Terkirim',
                        $row->procedure_status === 'gagal' => 'Gagal',
                        default => 'Belum Terkirim',
                    },
                    'warna' => match (true) {
                        $sent => 'emerald',
                        $row->procedure_status === 'gagal' => 'rose',
                        default => 'amber',
                    },
                ];
            });
    }
}
