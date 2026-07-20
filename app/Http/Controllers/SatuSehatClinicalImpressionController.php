<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SatuSehatClinicalImpressionController extends Controller
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
            $rows = $this->clinicalImpressionRows($filters);
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
            $rows = collect();
        }

        return view('satu-sehat-encounter', [
            'resourceType' => 'clinical-impression',
            'rows' => $rows,
            'dbError' => $dbError,
            'usingFallback' => $dbError !== null,
            'filters' => $filters,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    private function clinicalImpressionRows(array $filters): Collection
    {
        $primaryDiagnosis = DB::table('diagnosa_pasien')
            ->select([
                'no_rawat',
                DB::raw("
                    COALESCE(
                        MIN(CASE WHEN prioritas = 1 THEN kd_penyakit END),
                        MIN(kd_penyakit)
                    ) as kd_penyakit
                "),
            ])
            ->where('status', 'Ralan')
            ->groupBy('no_rawat');

        $query = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('pemeriksaan_ralan', 'pemeriksaan_ralan.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pegawai', 'pemeriksaan_ralan.nip', '=', 'pegawai.nik')
            ->leftJoin('nota_jalan', 'nota_jalan.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('satu_sehat_encounter_new', 'satu_sehat_encounter_new.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoinSub($primaryDiagnosis, 'diagnosa_utama', function ($join) {
                $join->on('diagnosa_utama.no_rawat', '=', 'reg_periksa.no_rawat');
            })
            ->leftJoin('satu_sehat_condition_new', function ($join) {
                $join->on('satu_sehat_condition_new.no_rawat', '=', 'reg_periksa.no_rawat')
                    ->on('satu_sehat_condition_new.kd_penyakit', '=', 'diagnosa_utama.kd_penyakit');
            })
            ->leftJoin('penyakit', 'penyakit.kd_penyakit', '=', 'satu_sehat_condition_new.kd_penyakit')
            ->leftJoin('satu_sehat_clinicalimpression_new', 'satu_sehat_clinicalimpression_new.no_rawat', '=', 'reg_periksa.no_rawat')
            ->whereNotNull('pemeriksaan_ralan.penilaian')
            ->where('pemeriksaan_ralan.penilaian', '<>', '')
            ->where(function ($subQuery) use ($filters) {
                $subQuery->whereBetween('pemeriksaan_ralan.tgl_perawatan', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('satu_sehat_clinicalimpression_new.created_at', [
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
                DB::raw("IFNULL(CONCAT(nota_jalan.tanggal, 'T', nota_jalan.jam, '+07:00'), '') as pulang"),
                DB::raw("IFNULL(satu_sehat_encounter_new.id_encounter, '') as id_encounter"),
                'pegawai.nama',
                DB::raw('pegawai.no_ktp as ktp_praktisi'),
                'pemeriksaan_ralan.tgl_perawatan',
                'pemeriksaan_ralan.jam_rawat',
                'pemeriksaan_ralan.penilaian',
                'pemeriksaan_ralan.keluhan',
                'pemeriksaan_ralan.pemeriksaan',
                'satu_sehat_condition_new.kd_penyakit',
                'penyakit.nm_penyakit',
                DB::raw("IFNULL(satu_sehat_condition_new.id_condition, '') as id_condition"),
                DB::raw("IFNULL(satu_sehat_clinicalimpression_new.clinicalimpression_id, '') as clinicalimpression_id"),
                DB::raw('satu_sehat_clinicalimpression_new.status as clinicalimpression_status'),
            ]);

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('reg_periksa.no_rawat', 'like', $search)
                    ->orWhere('reg_periksa.no_rkm_medis', 'like', $search)
                    ->orWhere('pasien.nm_pasien', 'like', $search)
                    ->orWhere('pasien.no_ktp', 'like', $search)
                    ->orWhere('pegawai.no_ktp', 'like', $search)
                    ->orWhere('pegawai.nama', 'like', $search)
                    ->orWhere('pemeriksaan_ralan.penilaian', 'like', $search)
                    ->orWhere('reg_periksa.stts', 'like', $search);
            });
        }

        return $query
            ->orderByDesc('pemeriksaan_ralan.tgl_perawatan')
            ->orderByDesc('pemeriksaan_ralan.jam_rawat')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                $sent = $row->clinicalimpression_status === 'berhasil' && filled($row->clinicalimpression_id);

                return [
                    'waktu_pemeriksaan' => $row->tgl_perawatan . 'T' . $row->jam_rawat . '+07:00',
                    'waktu_registrasi' => $row->tgl_registrasi . 'T' . $row->jam_reg . '+07:00',
                    'no_rawat' => $row->no_rawat,
                    'norm' => $row->no_rkm_medis,
                    'pasien' => $row->nm_pasien ?: '-',
                    'nik_pasien' => $row->no_ktp ?: '-',
                    'stts' => $row->stts ?: '-',
                    'pulang' => $row->pulang ?: '-',
                    'id_encounter' => $row->id_encounter ?: '-',
                    'praktisi' => $row->nama ?: '-',
                    'nik_praktisi' => $row->ktp_praktisi ?: '-',
                    'penilaian' => $row->penilaian ?: '-',
                    'keluhan' => $row->keluhan ?: '-',
                    'pemeriksaan' => $row->pemeriksaan ?: '-',
                    'kd_penyakit' => $row->kd_penyakit ?: '-',
                    'penyakit' => $row->nm_penyakit ?: '-',
                    'id_condition' => $row->id_condition ?: '-',
                    'id_clinical_impression' => $row->clinicalimpression_id ?: '-',
                    'status_clinical_impression' => match (true) {
                        $sent => 'Sudah Terkirim',
                        $row->clinicalimpression_status === 'gagal' => 'Gagal',
                        default => 'Belum Terkirim',
                    },
                    'warna' => match (true) {
                        $sent => 'emerald',
                        $row->clinicalimpression_status === 'gagal' => 'rose',
                        default => 'amber',
                    },
                ];
            });
    }
}
