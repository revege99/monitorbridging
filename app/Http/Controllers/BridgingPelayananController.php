<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class BridgingPelayananController extends Controller
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
            $rows = $this->fallbackRows($filters['jenis']);
            $rows = $this->applyFilter($rows, $filters);
        }

        $summary = $this->buildSummary($rows, $filters['jenis']);

        return view('bridging-pelayanan', [
            'rows' => $rows,
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
        $jenis = $request->query('jenis', 'rajal');

        if (! in_array($jenis, ['rajal', 'rujuk'], true)) {
            $jenis = 'rajal';
        }

        $statusKey = $jenis === 'rujuk' ? 'status_rujuk' : 'status_bridging';
        $allowedStatus = $jenis === 'rujuk'
            ? ['all', 'dengan_tacc', 'tanpa_tacc']
            : ['all', 'same_day', 'cross_day', 'not_bridged'];
        $status = $request->query($statusKey, 'all');

        if (! in_array($status, $allowedStatus, true)) {
            $status = 'all';
        }

        return [
            'jenis' => $jenis,
            'search' => trim((string) $request->query('search', '')),
            'start_date' => $request->query('start_date', $today) ?: $today,
            'end_date' => $request->query('end_date', $today) ?: $today,
            'status_bridging' => $jenis === 'rajal' ? $status : 'all',
            'status_rujuk' => $jenis === 'rujuk' ? $status : 'all',
        ];
    }

    private function getRowsFromDatabase(array $filters): Collection
    {
        return $filters['jenis'] === 'rujuk'
            ? $this->getRujukRowsFromDatabase($filters)
            : $this->getRajalRowsFromDatabase($filters);
    }

    private function getRajalRowsFromDatabase(array $filters): Collection
    {
        $rows = DB::table('reg_periksa as rp')
            ->leftJoin('pasien as ps', 'ps.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->leftJoin('poliklinik as pl', 'pl.kd_poli', '=', 'rp.kd_poli')
            ->leftJoin('pcare_pendaftaran as pp', 'pp.no_rawat', '=', 'rp.no_rawat')
            ->leftJoin('pcare_kunjungan_umum as pk', 'pk.no_rawat', '=', 'rp.no_rawat')
            ->select([
                'rp.no_rawat',
                'rp.no_rkm_medis',
                'rp.kd_poli',
                'rp.tgl_registrasi',
                'rp.jam_reg',
                'ps.nm_pasien',
                'pl.nm_poli',
                'pp.tglDaftar as pcare_daftar',
                'pk.tglDaftar as pcare_kunjungan',
            ])
            ->where('rp.kd_pj', 'BPJ')
            ->where('rp.stts', '!=', 'Batal')
            ->where(function ($query) {
                $query->whereNotNull('pk.no_rawat')
                    ->orWhereNotExists(function ($subQuery) {
                        $subQuery->selectRaw('1')
                            ->from('pcare_rujuk_subspesialis as prs')
                            ->whereColumn('prs.no_rawat', 'rp.no_rawat');
                    });
            });

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
                    ->orWhere('ps.nm_pasien', 'like', '%' . $search . '%');
            });
        }

        return $this->applyFilter(
            $rows->orderByDesc('rp.tgl_registrasi')
                ->orderByDesc('rp.jam_reg')
                ->limit(100)
                ->get()
                ->map(function ($row) {
                    $regDate = $row->tgl_registrasi ? Carbon::parse($row->tgl_registrasi) : null;
                    $daftarDate = $row->pcare_daftar ? Carbon::parse($row->pcare_daftar) : null;
                    $kunjunganDate = $row->pcare_kunjungan ? Carbon::parse($row->pcare_kunjungan) : null;
                    $hasDaftar = (bool) $daftarDate;
                    $hasKunjungan = (bool) $kunjunganDate;

                    $status = 'Belum Bridging';
                    $color = 'rose';
                    $note = 'Sudah ada di reg_periksa, tetapi belum muncul di tabel PCare.';

                    if ($hasDaftar && ! $hasKunjungan) {
                        if ($regDate && $daftarDate && $daftarDate->isSameDay($regDate)) {
                            $status = 'Pendaftaran Saja';
                            $color = 'rose';
                            $note = 'Pendaftaran PCare sudah ada di hari yang sama, tetapi bridging kunjungan belum tersedia. Bridging belum sukses.';
                        } elseif ($regDate && $daftarDate && $daftarDate->greaterThan($regDate)) {
                            $diffDays = $regDate->diffInDays($daftarDate);
                            $status = 'Pendaftaran H+' . $diffDays;
                            $color = 'amber';
                            $note = 'Pendaftaran PCare tersedia, tetapi bridging kunjungan belum ada sehingga bridging belum lengkap.';
                        } else {
                            $status = 'Pendaftaran Saja';
                            $color = 'rose';
                            $note = 'Pendaftaran PCare sudah tersedia, tetapi bridging kunjungan belum ada sehingga bridging belum sukses.';
                        }
                    } elseif ($hasDaftar && $hasKunjungan) {
                        $compareDate = $kunjunganDate ?: $daftarDate;

                        if ($regDate && $compareDate && $compareDate->isSameDay($regDate)) {
                            $status = 'Same Day';
                            $color = 'emerald';
                            $note = 'Registrasi, pendaftaran PCare, dan kunjungan tercatat lengkap di hari yang sama.';
                        } elseif ($regDate && $compareDate && $compareDate->greaterThan($regDate)) {
                            $diffDays = $regDate->diffInDays($compareDate);
                            $status = 'Kunjungan H+' . $diffDays;
                            $color = 'amber';
                            $note = 'Pendaftaran sudah ada, tetapi kunjungan tercatat melewati tanggal registrasi.';
                        } else {
                            $status = 'Bridging Lengkap';
                            $color = 'emerald';
                            $note = 'Data pendaftaran dan kunjungan PCare ditemukan lengkap.';
                        }
                    } elseif ($hasKunjungan) {
                        if ($regDate && $kunjunganDate && $kunjunganDate->isSameDay($regDate)) {
                            $status = 'Kunjungan Saja';
                            $color = 'amber';
                            $note = 'Kunjungan PCare ada, tetapi pendaftaran belum ditemukan. Perlu dicek kembali.';
                        } elseif ($regDate && $kunjunganDate && $kunjunganDate->greaterThan($regDate)) {
                            $diffDays = $regDate->diffInDays($kunjunganDate);
                            $status = 'Kunjungan H+' . $diffDays;
                            $color = 'amber';
                            $note = 'Kunjungan PCare tersedia, tetapi data pendaftaran belum lengkap.';
                        } else {
                            $status = 'Kunjungan Saja';
                            $color = 'amber';
                            $note = 'Kunjungan PCare ditemukan tanpa data pendaftaran. Perlu validasi bridging.';
                        }
                    }

                    return [
                        'no_rawat' => $row->no_rawat,
                        'norm' => $row->no_rkm_medis,
                        'pasien' => $row->nm_pasien ?: '-',
                        'poli' => $row->nm_poli ?: $row->kd_poli ?: '-',
                        'reg_tanggal' => $row->tgl_registrasi,
                        'reg_jam' => $row->jam_reg,
                        'pcare_daftar' => $row->pcare_daftar,
                        'pcare_kunjungan' => $row->pcare_kunjungan,
                        'status' => $status,
                        'warna' => $color,
                        'catatan' => $note,
                    ];
                }),
            $filters
        );
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
                'prs.nmTACC',
                'prs.alasanTACC',
                'prs.nmDiag1 as diag_rujuk',
                'prs.tglEstRujuk',
                'dp.kd_penyakit',
                'dp.nonSpesialis',
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
                    ->orWhere('dp.nonSpesialis', 'like', '%' . $search . '%');
            });
        }

        return $this->applyFilter(
            $rows->orderByDesc('rp.tgl_registrasi')
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
                        'diagnosa' => $row->nonSpesialis ?: '-',
                        'kode_diagnosa' => $row->kd_penyakit ?: '-',
                        'non_spesialis' => $row->nonSpesialis ?: '-',
                        'tacc' => $row->nmTACC ?: '-',
                        'alasan_tacc' => $row->alasanTACC ?: '-',
                    ];
                }),
            $filters
        );
    }

    private function applyFilter(Collection $rows, array $filters): Collection
    {
        if ($filters['jenis'] === 'rujuk') {
            return match ($filters['status_rujuk']) {
                'dengan_tacc' => $rows->filter(fn ($row) => $row['tacc'] !== '-')->values(),
                'tanpa_tacc' => $rows->filter(fn ($row) => $row['tacc'] === '-')->values(),
                default => $rows->values(),
            };
        }

        return match ($filters['status_bridging']) {
            'same_day' => $rows->filter(fn ($row) => $row['warna'] === 'emerald' && $row['status'] === 'Same Day')->values(),
            'cross_day' => $rows->filter(fn ($row) => $row['warna'] === 'amber')->values(),
            'not_bridged' => $rows->filter(fn ($row) => $row['warna'] === 'rose')->values(),
            default => $rows->values(),
        };
    }

    private function fallbackRows(string $jenis): Collection
    {
        if ($jenis === 'rujuk') {
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

        return collect([
            [
                'no_rawat' => '2026/07/15/000001',
                'norm' => '000145',
                'pasien' => 'SITI AMINAH',
                'poli' => 'POLI UMUM',
                'reg_tanggal' => '2026-07-15',
                'reg_jam' => '07:12:08',
                'pcare_daftar' => '2026-07-15',
                'pcare_kunjungan' => '2026-07-15',
                'status' => 'Same Day',
                'warna' => 'emerald',
                'catatan' => 'Registrasi, pendaftaran PCare, dan kunjungan ada di hari yang sama.',
            ],
            [
                'no_rawat' => '2026/07/15/000014',
                'norm' => '000233',
                'pasien' => 'BUDI SETIAWAN',
                'poli' => 'POLI GIGI',
                'reg_tanggal' => '2026-07-15',
                'reg_jam' => '09:24:51',
                'pcare_daftar' => '2026-07-15',
                'pcare_kunjungan' => '2026-07-16',
                'status' => 'Kunjungan H+1',
                'warna' => 'amber',
                'catatan' => 'Pasien terdaftar hari ini, tetapi bridging kunjungan tercatat keesokan hari.',
            ],
            [
                'no_rawat' => '2026/07/15/000029',
                'norm' => '000981',
                'pasien' => 'RINA KARTIKA',
                'poli' => 'POLI UMUM',
                'reg_tanggal' => '2026-07-15',
                'reg_jam' => '10:48:10',
                'pcare_daftar' => null,
                'pcare_kunjungan' => null,
                'status' => 'Belum Bridging',
                'warna' => 'rose',
                'catatan' => 'Sudah ada di reg_periksa, tetapi belum masuk ke pcare_pendaftaran maupun pcare_kunjungan_umum.',
            ],
        ]);
    }

    private function buildSummary(Collection $rows, string $jenis): array
    {
        if ($jenis === 'rujuk') {
            $nonSpesialis = $rows->filter(function ($row) {
                return ! in_array(strtolower(trim((string) $row['non_spesialis'])), ['', '-', 'false', '0', 'tidak'], true);
            })->count();

            return [
                'total' => $rows->count(),
                'non_spesialis' => $nonSpesialis,
                'spesialis' => $rows->count() - $nonSpesialis,
            ];
        }

        return [
            'total' => $rows->count(),
            'same_day' => $rows->filter(fn ($row) => $row['warna'] === 'emerald')->count(),
            'cross_day' => $rows->filter(fn ($row) => $row['warna'] === 'amber')->count(),
            'not_bridged' => $rows->filter(fn ($row) => $row['warna'] === 'rose')->count(),
        ];
    }
}
