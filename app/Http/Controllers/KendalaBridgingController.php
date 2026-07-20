<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class KendalaBridgingController extends Controller
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

        return view('kendala-bridging', [
            'kendalaRows' => $rows,
            'summary' => $this->buildSummary($rows),
            'dbError' => $dbError,
            'usingFallback' => $usingFallback,
            'filters' => $filters,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    public function perbaiki(Request $request)
    {
        $no_rawat = (string) $request->input('no_rawat');

        if ($no_rawat === '') {
            return redirect()
                ->route('monitoring.kendala-bridging', $request->query())
                ->with('error', 'No rawat tidak boleh kosong.');
        }

        try {
            $deleted = DB::table('pcare_kunjungan_umum')
                ->where('no_rawat', $no_rawat)
                ->delete();

            if ($deleted === 0) {
                return redirect()
                    ->route('monitoring.kendala-bridging', $request->query())
                    ->with('error', 'Data kunjungan PCare untuk no rawat tersebut tidak ditemukan.');
            }

            return redirect()
                ->route('monitoring.kendala-bridging', $request->query())
                ->with('success', 'Data kunjungan PCare berhasil dihapus. Silakan bridging ulang dari klinik.');
        } catch (Throwable $exception) {
            return redirect()
                ->route('monitoring.kendala-bridging', $request->query())
                ->with('error', 'Gagal menghapus data kunjungan PCare: '.$exception->getMessage());
        }
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
            ->join('pcare_pendaftaran as pp', 'pp.no_rawat', '=', 'rp.no_rawat')
            ->join('pcare_kunjungan_umum as pk', 'pk.no_rawat', '=', 'rp.no_rawat')
            ->select([
                'rp.no_rawat',
                'rp.no_rkm_medis',
                'rp.tgl_registrasi',
                'rp.jam_reg',
                'ps.nm_pasien',
                'pl.nm_poli',
                'rp.kd_poli',
                'pp.tglDaftar as tgl_pendaftaran',
                'pk.tglDaftar as tgl_kunjungan',
                'pk.noKunjungan',
                'pp.status as status_pendaftaran',
                'pk.status as status_kunjungan',
            ])
            ->where('rp.kd_pj', 'BPJ')
            ->where('rp.stts', '!=', 'Batal')
            ->whereDate('rp.tgl_registrasi', '>=', $filters['start_date'])
            ->whereDate('rp.tgl_registrasi', '<=', $filters['end_date'])
            ->where(function ($query) {
                $query->whereNull('pk.noKunjungan')
                    ->orWhere('pk.noKunjungan', '=', '')
                    ->orWhere('pk.noKunjungan', '=', '0');
            })
            ->orderByDesc('rp.tgl_registrasi')
            ->orderByDesc('rp.jam_reg')
            ->limit(100)
            ->get()
            ->map(function ($row) {
                $regDate = $row->tgl_registrasi ? Carbon::parse($row->tgl_registrasi) : null;
                $kunjunganDate = $row->tgl_kunjungan ? Carbon::parse($row->tgl_kunjungan) : null;
                $isSameDay = $regDate && $kunjunganDate && $regDate->isSameDay($kunjunganDate);

                return [
                    'no_rawat' => $row->no_rawat,
                    'norm' => $row->no_rkm_medis,
                    'pasien' => $row->nm_pasien ?: '-',
                    'poli' => $row->nm_poli ?: $row->kd_poli ?: '-',
                    'reg_tanggal' => $row->tgl_registrasi,
                    'reg_jam' => $row->jam_reg,
                    'pcare_daftar' => $row->tgl_pendaftaran ?: '-',
                    'pcare_kunjungan' => $row->tgl_kunjungan ?: '-',
                    'no_kunjungan' => $row->noKunjungan ?: '-',
                    'status_pendaftaran' => $row->status_pendaftaran ?: '-',
                    'status_kunjungan' => $row->status_kunjungan ?: '-',
                    'status_hari' => $isSameDay ? 'Same Day' : 'Beda Hari',
                    'warna' => $isSameDay ? 'emerald' : 'amber',
                    'catatan' => 'Pendaftaran dan kunjungan PCare ada, tetapi noKunjungan masih kosong.',
                ];
            });
    }

    private function fallbackRows(): Collection
    {
        return collect([
            [
                'no_rawat' => '2026/07/17/000021',
                'norm' => '000551',
                'pasien' => 'BUDI SAPUTRA',
                'poli' => 'POLIKLINIK UMUM',
                'reg_tanggal' => '2026-07-17',
                'reg_jam' => '09:12:33',
                'pcare_daftar' => '2026-07-17',
                'pcare_kunjungan' => '2026-07-17',
                'no_kunjungan' => '-',
                'status_pendaftaran' => 'Sukses',
                'status_kunjungan' => 'Sukses',
                'status_hari' => 'Same Day',
                'warna' => 'emerald',
                'catatan' => 'Pendaftaran dan kunjungan PCare ada, tetapi noKunjungan masih kosong.',
            ],
        ]);
    }

    private function buildSummary(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            'same_day' => $rows->filter(fn ($row) => $row['status_hari'] === 'Same Day')->count(),
            'cross_day' => $rows->filter(fn ($row) => $row['status_hari'] === 'Beda Hari')->count(),
            'no_kunjungan_kosong' => $rows->filter(fn ($row) => in_array($row['no_kunjungan'], ['-', '', '0'], true))->count(),
        ];
    }
}
