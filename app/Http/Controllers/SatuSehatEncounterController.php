<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SatuSehatEncounterController extends Controller
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
            $rows = $this->applySearchFilter($rows, $filters['search']);
        }

        return view('satu-sehat-encounter', [
            'rows' => $rows,
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
            'search' => trim((string) $request->query('search', '')),
            'start_date' => $request->query('start_date', $today) ?: $today,
            'end_date' => $request->query('end_date', $today) ?: $today,
        ];
    }

    private function getRowsFromDatabase(array $filters): Collection
    {
        return $this->getEncounterRows($filters);
    }

    private function getEncounterRows(array $filters): Collection
    {
        $query = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('pegawai', 'pegawai.nik', '=', 'reg_periksa.kd_dokter')
            ->join('poliklinik', 'poliklinik.kd_poli', '=', 'reg_periksa.kd_poli')
            ->leftJoin('satu_sehat_mapping_lokasi_ralan', 'satu_sehat_mapping_lokasi_ralan.kd_poli', '=', 'poliklinik.kd_poli')
            ->leftJoin('nota_jalan', 'nota_jalan.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('nota_inap', 'nota_inap.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('satu_sehat_encounter_new', 'satu_sehat_encounter_new.no_rawat', '=', 'reg_periksa.no_rawat')
            ->select([
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.no_ktp',
                'reg_periksa.kd_dokter',
                'pegawai.nama',
                DB::raw('pegawai.no_ktp as ktp_dokter'),
                'reg_periksa.kd_poli',
                'poliklinik.nm_poli',
                'satu_sehat_mapping_lokasi_ralan.id_lokasi_satusehat',
                'reg_periksa.stts',
                'reg_periksa.status_lanjut',
                DB::raw("
                    CASE
                        WHEN nota_inap.tanggal IS NOT NULL THEN CONCAT(nota_inap.tanggal,'T',nota_inap.jam,'+07:00')
                        WHEN nota_jalan.tanggal IS NOT NULL THEN CONCAT(nota_jalan.tanggal,'T',nota_jalan.jam,'+07:00')
                        ELSE ''
                    END as pulang
                "),
                DB::raw("IFNULL(satu_sehat_encounter_new.id_encounter,'') as id_encounter"),
                DB::raw('satu_sehat_encounter_new.status as encounter_status'),
            ])
            ->where(function ($query) use ($filters) {
                $query->whereBetween('reg_periksa.tgl_registrasi', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('satu_sehat_encounter_new.created_at', [
                        $filters['start_date'] . ' 00:00:00',
                        $filters['end_date'] . ' 23:59:59',
                    ]);
            });

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';

            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('reg_periksa.no_rawat', 'like', $search)
                    ->orWhere('reg_periksa.no_rkm_medis', 'like', $search)
                    ->orWhere('pasien.nm_pasien', 'like', $search)
                    ->orWhere('pasien.no_ktp', 'like', $search)
                    ->orWhere('pegawai.nama', 'like', $search)
                    ->orWhere('poliklinik.nm_poli', 'like', $search)
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
            $waktuRegistrasi = $row->tgl_registrasi . 'T' . $row->jam_reg . '+07:00';
            $hasEncounter = $row->encounter_status === 'success' && filled($row->id_encounter);
            $jenisLayanan = strcasecmp((string) $row->status_lanjut, 'Ranap') === 0 ? 'Rawat Inap' : 'Rawat Jalan';

            return [
                'waktu_registrasi' => $waktuRegistrasi,
                'waktu_registrasi_sort' => $waktuRegistrasi,
                'no_rawat' => $row->no_rawat,
                'norm' => $row->no_rkm_medis,
                'pasien' => $row->nm_pasien ?: '-',
                'nik_pasien' => $row->no_ktp ?: '-',
                'kd_dokter' => $row->kd_dokter ?: '-',
                'dokter' => $row->nama ?: '-',
                'nik_dokter' => $row->ktp_dokter ?: '-',
                'kd_poli' => $row->kd_poli ?: '-',
                'poli' => $row->nm_poli ?: '-',
                'id_lokasi_satusehat' => $row->id_lokasi_satusehat ?: '-',
                'stts' => $row->stts ?: '-',
                'status_lanjut' => $row->status_lanjut ?: '-',
                'pulang' => $row->pulang ?: '-',
                'id_encounter' => $row->id_encounter ?: '-',
                'jenis_layanan' => $jenisLayanan,
                'status_encounter' => match (true) {
                    $hasEncounter => 'Sudah Terkirim',
                    $row->encounter_status === 'failed' => 'Gagal',
                    default => 'Belum Terkirim',
                },
                'warna' => match (true) {
                    $hasEncounter => 'emerald',
                    $row->encounter_status === 'failed' => 'rose',
                    default => 'amber',
                },
            ];
        });
    }

    private function applySearchFilter(Collection $rows, string $search): Collection
    {
        if ($search === '') {
            return $rows->values();
        }

        $keyword = mb_strtolower($search);

        return $rows->filter(function ($row) use ($keyword) {
            return str_contains(mb_strtolower(
                implode(' ', [
                    $row['no_rawat'],
                    $row['norm'],
                    $row['pasien'],
                    $row['nik_pasien'],
                    $row['dokter'],
                    $row['poli'],
                    $row['stts'],
                    $row['status_lanjut'],
                ])
            ), $keyword);
        })->values();
    }

    private function buildSummary(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            'terkirim' => $rows->filter(fn ($row) => $row['id_encounter'] !== '-')->count(),
            'belum_terkirim' => $rows->filter(fn ($row) => $row['id_encounter'] === '-')->count(),
            'rawat_jalan' => $rows->filter(fn ($row) => $row['jenis_layanan'] === 'Rawat Jalan')->count(),
            'rawat_inap' => $rows->filter(fn ($row) => $row['jenis_layanan'] === 'Rawat Inap')->count(),
        ];
    }

    private function fallbackRows(): Collection
    {
        return collect([
            [
                'waktu_registrasi' => '2026-07-18T08:14:21+07:00',
                'waktu_registrasi_sort' => '2026-07-18T08:14:21+07:00',
                'no_rawat' => '2026/07/18/000021',
                'norm' => '000112',
                'pasien' => 'BUDI HARTONO',
                'nik_pasien' => '1271020202800001',
                'kd_dokter' => 'D001',
                'dokter' => 'dr. ANDI SAPUTRA',
                'nik_dokter' => '1271020202800011',
                'kd_poli' => 'U0001',
                'poli' => 'POLIKLINIK UMUM',
                'id_lokasi_satusehat' => 'LOC-RJ-001',
                'stts' => 'Sudah',
                'status_lanjut' => 'Ralan',
                'pulang' => '2026-07-18T10:02:11+07:00',
                'id_encounter' => 'ENC-20260718-000021',
                'jenis_layanan' => 'Rawat Jalan',
                'status_encounter' => 'Sudah Terkirim',
                'warna' => 'emerald',
            ],
            [
                'waktu_registrasi' => '2026-07-18T09:35:42+07:00',
                'waktu_registrasi_sort' => '2026-07-18T09:35:42+07:00',
                'no_rawat' => '2026/07/18/000045',
                'norm' => '000287',
                'pasien' => 'SITI MAULIDA',
                'nik_pasien' => '1271020202800002',
                'kd_dokter' => 'D014',
                'dokter' => 'dr. RIZKY PRATAMA',
                'nik_dokter' => '1271020202800022',
                'kd_poli' => 'I0002',
                'poli' => 'PENYAKIT DALAM',
                'id_lokasi_satusehat' => 'LOC-RI-002',
                'stts' => 'Sudah',
                'status_lanjut' => 'Ranap',
                'pulang' => '2026-07-18T15:40:00+07:00',
                'id_encounter' => '-',
                'jenis_layanan' => 'Rawat Inap',
                'status_encounter' => 'Belum Terkirim',
                'warna' => 'amber',
            ],
        ]);
    }
}
