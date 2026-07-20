<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TimelinePelayananController extends Controller
{
    public function index(Request $request)
    {
        $dbError = null;
        $filters = $this->buildFilters($request);

        try {
            $timelineRows = $this->getTimelineRowsFromDatabase($filters);
            $summary = $this->buildSummaryFromDatabase($filters);
        } catch (\Throwable $exception) {
            $dbError = $exception->getMessage();
            $timelineRows = collect();
            $summary = $this->emptySummary($filters['jenis']);
        }

        return view('timeline-pelayanan', [
            'timelineRows' => $timelineRows,
            'summary' => $summary,
            'dbError' => $dbError,
            'usingFallback' => false,
            'filters' => $filters,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    private function buildFilters(Request $request): array
    {
        $today = now()->toDateString();
        $jenis = $request->query('jenis', 'rawat-jalan');

        if (! in_array($jenis, ['rawat-jalan', 'rujukan'], true)) {
            $jenis = 'rawat-jalan';
        }

        $allowedTahap = $jenis === 'rujukan'
            ? ['all', 'tacc', 'tanpa_tacc']
            : ['all', 'registrasi', 'pendaftaran', 'kunjungan'];
        $tahap = $request->query('tahap', 'all');

        if (! in_array($tahap, $allowedTahap, true)) {
            $tahap = 'all';
        }

        return [
            'search' => trim((string) $request->query('search', '')),
            'start_date' => $request->query('start_date', $today) ?: $today,
            'end_date' => $request->query('end_date', $today) ?: $today,
            'tahap' => $tahap,
            'jenis' => $jenis,
        ];
    }

    private function emptySummary(string $jenis): array
    {
        if ($jenis === 'rujukan') {
            return [
                'total' => 0,
                'tacc' => 0,
                'tanpa_tacc' => 0,
                'cross_day' => 0,
            ];
        }

        return [
            'total' => 0,
            'registrasi' => 0,
            'pendaftaran' => 0,
            'kunjungan' => 0,
            'cross_day' => 0,
        ];
    }

    private function getTimelineRowsFromDatabase(array $filters): Collection
    {
        return $filters['jenis'] === 'rujukan'
            ? $this->getRujukanRowsFromDatabase($filters)
            : $this->getRawatJalanRowsFromDatabase($filters);
    }

    private function buildSummaryFromDatabase(array $filters): array
    {
        return $filters['jenis'] === 'rujukan'
            ? $this->buildRujukanSummaryFromDatabase($filters)
            : $this->buildRawatJalanSummaryFromDatabase($filters);
    }

    private function getRawatJalanRowsFromDatabase(array $filters): Collection
    {
        $search = '%' . $filters['search'] . '%';

        $rows = collect(DB::select(
            <<<'SQL'
            SELECT
                rp.no_rawat,
                rp.no_rkm_medis,
                COALESCE(ps.nm_pasien, '-') AS nm_pasien,
                COALESCE(pl.nm_poli, rp.kd_poli) AS nm_poli,
                rp.tgl_registrasi,
                rp.jam_reg,
                pp.tglDaftar AS tgl_pcare_daftar,
                pk.tglDaftar AS tgl_pcare_kunjungan,
                CASE
                    WHEN pk.no_rawat IS NOT NULL THEN 'Kunjungan Selesai'
                    WHEN pp.no_rawat IS NOT NULL THEN 'Pendaftaran PCare'
                    ELSE 'Registrasi'
                END AS tahap_akhir,
                CASE
                    WHEN pk.tglDaftar IS NOT NULL THEN DATEDIFF(pk.tglDaftar, rp.tgl_registrasi)
                    WHEN pp.tglDaftar IS NOT NULL THEN DATEDIFF(pp.tglDaftar, rp.tgl_registrasi)
                    ELSE 0
                END AS total_tunggu_hari
            FROM reg_periksa rp
            LEFT JOIN pasien ps
                ON ps.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN poliklinik pl
                ON pl.kd_poli = rp.kd_poli
            LEFT JOIN pcare_pendaftaran pp
                ON pp.no_rawat = rp.no_rawat
            LEFT JOIN pcare_kunjungan_umum pk
                ON pk.no_rawat = rp.no_rawat
            WHERE rp.kd_pj = 'BPJ'
              AND rp.stts != 'Batal'
              AND NOT EXISTS (
                    SELECT 1
                    FROM pcare_rujuk_subspesialis prs
                    WHERE prs.no_rawat = rp.no_rawat
                  )
              AND rp.tgl_registrasi BETWEEN ? AND ?
              AND (
                    ? = ''
                    OR rp.no_rawat LIKE ?
                    OR rp.no_rkm_medis LIKE ?
                    OR ps.nm_pasien LIKE ?
                  )
              AND (
                    ? = 'all'
                    OR (? = 'registrasi' AND pp.no_rawat IS NULL AND pk.no_rawat IS NULL)
                    OR (? = 'pendaftaran' AND pp.no_rawat IS NOT NULL AND pk.no_rawat IS NULL)
                    OR (? = 'kunjungan' AND pk.no_rawat IS NOT NULL)
                  )
            ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC
            LIMIT 100
            SQL,
            [
                $filters['start_date'],
                $filters['end_date'],
                $filters['search'],
                $search,
                $search,
                $search,
                $filters['tahap'],
                $filters['tahap'],
                $filters['tahap'],
                $filters['tahap'],
            ]
        ));

        return $rows->map(function ($row) {
            $stageColor = match ($row->tahap_akhir) {
                'Kunjungan Selesai' => ((int) $row->total_tunggu_hari) > 0 ? 'amber' : 'emerald',
                'Pendaftaran PCare' => ((int) $row->total_tunggu_hari) > 0 ? 'amber' : 'blue',
                default => 'slate',
            };

            $note = match ($row->tahap_akhir) {
                'Kunjungan Selesai' => ((int) $row->total_tunggu_hari) > 0
                    ? 'Kunjungan umum tercatat setelah hari registrasi.'
                    : 'Registrasi, pendaftaran, dan kunjungan selesai di hari yang sama.',
                'Pendaftaran PCare' => ((int) $row->total_tunggu_hari) > 0
                    ? 'Pendaftaran PCare tercatat setelah hari registrasi.'
                    : 'Pendaftaran PCare sudah masuk, kunjungan umum belum tersedia.',
                default => 'Pasien baru tercatat di reg_periksa.',
            };

            return [
                'no_rawat' => $row->no_rawat,
                'norm' => $row->no_rkm_medis,
                'pasien' => $row->nm_pasien,
                'poli' => $row->nm_poli,
                'reg_tanggal' => $row->tgl_registrasi,
                'reg_jam' => $row->jam_reg,
                'pcare_daftar' => $row->tgl_pcare_daftar,
                'pcare_kunjungan' => $row->tgl_pcare_kunjungan,
                'tahap_akhir' => $row->tahap_akhir,
                'warna' => $stageColor,
                'catatan' => $note,
                'total_tunggu_hari' => (int) $row->total_tunggu_hari,
                'total_tunggu_label' => $row->total_tunggu_hari . ' Hari',
            ];
        });
    }

    private function getRujukanRowsFromDatabase(array $filters): Collection
    {
        $search = '%' . $filters['search'] . '%';

        $rows = collect(DB::select(
            <<<'SQL'
            SELECT
                rp.no_rawat,
                rp.no_rkm_medis,
                COALESCE(ps.nm_pasien, '-') AS nm_pasien,
                COALESCE(pl.nm_poli, rp.kd_poli) AS nm_poli,
                rp.tgl_registrasi,
                rp.jam_reg,
                prs.tglDaftar AS tgl_rujuk,
                prs.tglEstRujuk,
                prs.nmSubSpesialis,
                prs.nmPPK,
                prs.nmTACC,
                prs.alasanTACC,
                CASE
                    WHEN COALESCE(prs.nmTACC, '') != '' THEN 'Rujukan dengan TACC'
                    ELSE 'Rujukan Selesai'
                END AS tahap_akhir,
                DATEDIFF(prs.tglDaftar, rp.tgl_registrasi) AS total_tunggu_hari
            FROM reg_periksa rp
            INNER JOIN pcare_rujuk_subspesialis prs
                ON prs.no_rawat = rp.no_rawat
            LEFT JOIN pasien ps
                ON ps.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN poliklinik pl
                ON pl.kd_poli = rp.kd_poli
            WHERE rp.kd_pj = 'BPJ'
              AND rp.stts != 'Batal'
              AND rp.tgl_registrasi BETWEEN ? AND ?
              AND (
                    ? = ''
                    OR rp.no_rawat LIKE ?
                    OR rp.no_rkm_medis LIKE ?
                    OR ps.nm_pasien LIKE ?
                    OR prs.nmSubSpesialis LIKE ?
                    OR prs.nmPPK LIKE ?
                  )
              AND (
                    ? = 'all'
                    OR (? = 'tacc' AND COALESCE(prs.nmTACC, '') != '')
                    OR (? = 'tanpa_tacc' AND COALESCE(prs.nmTACC, '') = '')
                  )
            ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC
            LIMIT 100
            SQL,
            [
                $filters['start_date'],
                $filters['end_date'],
                $filters['search'],
                $search,
                $search,
                $search,
                $search,
                $search,
                $filters['tahap'],
                $filters['tahap'],
                $filters['tahap'],
            ]
        ));

        return $rows->map(function ($row) {
            $hasTacc = filled($row->nmTACC);
            $stageColor = $hasTacc ? 'amber' : (((int) $row->total_tunggu_hari) > 0 ? 'blue' : 'emerald');
            $note = $hasTacc
                ? 'Rujukan memiliki TACC dan perlu perhatian pada alasan rujukan.'
                : (((int) $row->total_tunggu_hari) > 0
                    ? 'Rujukan tercatat setelah hari registrasi.'
                    : 'Registrasi dan rujukan tercatat di hari yang sama.');

            return [
                'no_rawat' => $row->no_rawat,
                'norm' => $row->no_rkm_medis,
                'pasien' => $row->nm_pasien,
                'poli' => $row->nm_poli,
                'reg_tanggal' => $row->tgl_registrasi,
                'reg_jam' => $row->jam_reg,
                'tgl_rujuk' => $row->tgl_rujuk,
                'tgl_estimasi_rujuk' => $row->tglEstRujuk,
                'subspesialis' => $row->nmSubSpesialis ?: '-',
                'ppk_rujuk' => $row->nmPPK ?: '-',
                'tacc' => $row->nmTACC ?: '-',
                'alasan_tacc' => $row->alasanTACC ?: '-',
                'tahap_akhir' => $row->tahap_akhir,
                'warna' => $stageColor,
                'catatan' => $note,
                'total_tunggu_hari' => (int) $row->total_tunggu_hari,
                'total_tunggu_label' => $row->total_tunggu_hari . ' Hari',
            ];
        });
    }

    private function buildRawatJalanSummaryFromDatabase(array $filters): array
    {
        $search = '%' . $filters['search'] . '%';

        $summary = DB::selectOne(
            <<<'SQL'
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN pp.no_rawat IS NULL AND pk.no_rawat IS NULL THEN 1 ELSE 0 END) AS registrasi,
                SUM(CASE WHEN pp.no_rawat IS NOT NULL AND pk.no_rawat IS NULL THEN 1 ELSE 0 END) AS pendaftaran,
                SUM(CASE WHEN pk.no_rawat IS NOT NULL THEN 1 ELSE 0 END) AS kunjungan,
                SUM(
                    CASE
                        WHEN pk.tglDaftar IS NOT NULL AND DATEDIFF(pk.tglDaftar, rp.tgl_registrasi) != 0 THEN 1
                        WHEN pk.tglDaftar IS NULL AND pp.tglDaftar IS NOT NULL AND DATEDIFF(pp.tglDaftar, rp.tgl_registrasi) != 0 THEN 1
                        ELSE 0
                    END
                ) AS cross_day
            FROM reg_periksa rp
            LEFT JOIN pasien ps
                ON ps.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN pcare_pendaftaran pp
                ON pp.no_rawat = rp.no_rawat
            LEFT JOIN pcare_kunjungan_umum pk
                ON pk.no_rawat = rp.no_rawat
            WHERE rp.kd_pj = 'BPJ'
              AND rp.stts != 'Batal'
              AND NOT EXISTS (
                    SELECT 1
                    FROM pcare_rujuk_subspesialis prs
                    WHERE prs.no_rawat = rp.no_rawat
                  )
              AND rp.tgl_registrasi BETWEEN ? AND ?
              AND (
                    ? = ''
                    OR rp.no_rawat LIKE ?
                    OR rp.no_rkm_medis LIKE ?
                    OR ps.nm_pasien LIKE ?
                  )
              AND (
                    ? = 'all'
                    OR (? = 'registrasi' AND pp.no_rawat IS NULL AND pk.no_rawat IS NULL)
                    OR (? = 'pendaftaran' AND pp.no_rawat IS NOT NULL AND pk.no_rawat IS NULL)
                    OR (? = 'kunjungan' AND pk.no_rawat IS NOT NULL)
                  )
            SQL,
            [
                $filters['start_date'],
                $filters['end_date'],
                $filters['search'],
                $search,
                $search,
                $search,
                $filters['tahap'],
                $filters['tahap'],
                $filters['tahap'],
                $filters['tahap'],
            ]
        );

        return [
            'total' => (int) ($summary->total ?? 0),
            'registrasi' => (int) ($summary->registrasi ?? 0),
            'pendaftaran' => (int) ($summary->pendaftaran ?? 0),
            'kunjungan' => (int) ($summary->kunjungan ?? 0),
            'cross_day' => (int) ($summary->cross_day ?? 0),
        ];
    }

    private function buildRujukanSummaryFromDatabase(array $filters): array
    {
        $search = '%' . $filters['search'] . '%';

        $summary = DB::selectOne(
            <<<'SQL'
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN COALESCE(prs.nmTACC, '') != '' THEN 1 ELSE 0 END) AS tacc,
                SUM(CASE WHEN COALESCE(prs.nmTACC, '') = '' THEN 1 ELSE 0 END) AS tanpa_tacc,
                SUM(CASE WHEN DATEDIFF(prs.tglDaftar, rp.tgl_registrasi) != 0 THEN 1 ELSE 0 END) AS cross_day
            FROM reg_periksa rp
            INNER JOIN pcare_rujuk_subspesialis prs
                ON prs.no_rawat = rp.no_rawat
            LEFT JOIN pasien ps
                ON ps.no_rkm_medis = rp.no_rkm_medis
            WHERE rp.kd_pj = 'BPJ'
              AND rp.stts != 'Batal'
              AND rp.tgl_registrasi BETWEEN ? AND ?
              AND (
                    ? = ''
                    OR rp.no_rawat LIKE ?
                    OR rp.no_rkm_medis LIKE ?
                    OR ps.nm_pasien LIKE ?
                    OR prs.nmSubSpesialis LIKE ?
                    OR prs.nmPPK LIKE ?
                  )
              AND (
                    ? = 'all'
                    OR (? = 'tacc' AND COALESCE(prs.nmTACC, '') != '')
                    OR (? = 'tanpa_tacc' AND COALESCE(prs.nmTACC, '') = '')
                  )
            SQL,
            [
                $filters['start_date'],
                $filters['end_date'],
                $filters['search'],
                $search,
                $search,
                $search,
                $search,
                $search,
                $filters['tahap'],
                $filters['tahap'],
                $filters['tahap'],
            ]
        );

        return [
            'total' => (int) ($summary->total ?? 0),
            'tacc' => (int) ($summary->tacc ?? 0),
            'tanpa_tacc' => (int) ($summary->tanpa_tacc ?? 0),
            'cross_day' => (int) ($summary->cross_day ?? 0),
        ];
    }
}
