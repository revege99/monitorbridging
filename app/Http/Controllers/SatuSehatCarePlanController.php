<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SatuSehatCarePlanController extends Controller
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
            $rows = $this->carePlanRows($filters);
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
            $rows = collect();
        }

        return view('satu-sehat-encounter', [
            'resourceType' => 'care-plan',
            'rows' => $rows,
            'dbError' => $dbError,
            'usingFallback' => $dbError !== null,
            'filters' => $filters,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    private function carePlanRows(array $filters): Collection
    {
        $ralan = $this->examinationQuery('pemeriksaan_ralan', 'Ralan', $filters);
        $ranap = $this->examinationQuery('pemeriksaan_ranap', 'Ranap', $filters);

        return DB::query()
            ->fromSub($ralan->unionAll($ranap), 'care_plan_rows')
            ->orderByDesc('tgl_perawatan')
            ->orderByDesc('jam_rawat')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                $status = strtolower((string) $row->careplan_status);
                $sent = $status === 'berhasil' && filled($row->id_careplan);

                return [
                    'waktu_pemeriksaan' => $row->tgl_perawatan . 'T' . $row->jam_rawat . '+07:00',
                    'waktu_registrasi' => $row->tgl_registrasi . 'T' . $row->jam_reg . '+07:00',
                    'no_rawat' => $row->no_rawat,
                    'norm' => $row->no_rkm_medis,
                    'pasien' => $row->nm_pasien ?: '-',
                    'nik_pasien' => $row->no_ktp ?: '-',
                    'id_encounter' => $row->id_encounter ?: '-',
                    'rtl' => $row->rtl ?: '-',
                    'praktisi' => $row->nama ?: '-',
                    'nik_praktisi' => $row->ktp_praktisi ?: '-',
                    'jenis_layanan' => $row->jenis_layanan,
                    'id_careplan' => $row->id_careplan ?: '-',
                    'status_careplan' => match (true) {
                        $sent => 'Sudah Terkirim',
                        $status === 'gagal' => 'Gagal',
                        default => 'Belum Terkirim',
                    },
                    'warna' => match (true) {
                        $sent => 'emerald',
                        $status === 'gagal' => 'rose',
                        default => 'amber',
                    },
                ];
            });
    }

    private function examinationQuery(string $table, string $serviceType, array $filters): Builder
    {
        $query = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join($table, "{$table}.no_rawat", '=', 'reg_periksa.no_rawat')
            ->leftJoin('pegawai', "{$table}.nip", '=', 'pegawai.nik')
            ->leftJoin('satu_sehat_encounter_new', 'satu_sehat_encounter_new.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('satu_sehat_careplan_new', function ($join) use ($table) {
                $join->on('satu_sehat_careplan_new.no_rawat', '=', "{$table}.no_rawat")
                    ->on('satu_sehat_careplan_new.tgl_perawatan', '=', "{$table}.tgl_perawatan")
                    ->on('satu_sehat_careplan_new.jam_rawat', '=', "{$table}.jam_rawat");
            })
            ->whereNotNull("{$table}.rtl")
            ->where("{$table}.rtl", '<>', '')
            ->where(function ($subQuery) use ($table, $filters) {
                $subQuery->whereBetween("{$table}.tgl_perawatan", [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('satu_sehat_careplan_new.tanggal_kirim', [
                        $filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59',
                    ]);
            });

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($subQuery) use ($search, $table) {
                $subQuery->where('reg_periksa.no_rawat', 'like', $search)
                    ->orWhere('reg_periksa.no_rkm_medis', 'like', $search)
                    ->orWhere('pasien.nm_pasien', 'like', $search)
                    ->orWhere('pasien.no_ktp', 'like', $search)
                    ->orWhere('pegawai.no_ktp', 'like', $search)
                    ->orWhere('pegawai.nama', 'like', $search)
                    ->orWhere("{$table}.rtl", 'like', $search);
            });
        }

        return $query->select([
            'reg_periksa.tgl_registrasi', 'reg_periksa.jam_reg', 'reg_periksa.no_rawat',
            'reg_periksa.no_rkm_medis', 'pasien.nm_pasien', 'pasien.no_ktp',
            'satu_sehat_encounter_new.id_encounter', DB::raw("{$table}.rtl as rtl"),
            'pegawai.nama', DB::raw('pegawai.no_ktp as ktp_praktisi'),
            DB::raw("{$table}.tgl_perawatan as tgl_perawatan"), DB::raw("{$table}.jam_rawat as jam_rawat"),
            'satu_sehat_careplan_new.id_careplan', DB::raw('satu_sehat_careplan_new.status as careplan_status'),
            DB::raw("'{$serviceType}' as jenis_layanan"),
        ]);
    }
}
