<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SatuSehatMedicationDispenseController extends Controller
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
            $rows = $this->medicationDispenseRows($filters);
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
            $rows = collect();
        }

        return view('satu-sehat-encounter', [
            'resourceType' => 'medication-dispense',
            'rows' => $rows,
            'dbError' => $dbError,
            'usingFallback' => $dbError !== null,
            'filters' => $filters,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    private function medicationDispenseRows(array $filters): Collection
    {
        $query = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('resep_obat', 'resep_obat.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('pegawai', 'resep_obat.kd_dokter', '=', 'pegawai.nik')
            ->join('detail_pemberian_obat', function ($join) {
                $join->on('detail_pemberian_obat.no_rawat', '=', 'resep_obat.no_rawat')
                    ->on('detail_pemberian_obat.tgl_perawatan', '=', 'resep_obat.tgl_perawatan')
                    ->on('detail_pemberian_obat.jam', '=', 'resep_obat.jam');
            })
            ->leftJoin('aturan_pakai', function ($join) {
                $join->on('aturan_pakai.no_rawat', '=', 'detail_pemberian_obat.no_rawat')
                    ->on('aturan_pakai.tgl_perawatan', '=', 'detail_pemberian_obat.tgl_perawatan')
                    ->on('aturan_pakai.jam', '=', 'detail_pemberian_obat.jam')
                    ->on('aturan_pakai.kode_brng', '=', 'detail_pemberian_obat.kode_brng');
            })
            ->leftJoin('satu_sehat_encounter_new', 'satu_sehat_encounter_new.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('satu_sehat_mapping_obat', 'satu_sehat_mapping_obat.kode_brng', '=', 'detail_pemberian_obat.kode_brng')
            ->leftJoin('bangsal', 'bangsal.kd_bangsal', '=', 'detail_pemberian_obat.kd_bangsal')
            ->leftJoin('satu_sehat_mapping_lokasi_depo_farmasi', 'satu_sehat_mapping_lokasi_depo_farmasi.kd_bangsal', '=', 'bangsal.kd_bangsal')
            ->leftJoin('satu_sehat_medication', 'satu_sehat_medication.kode_brng', '=', 'detail_pemberian_obat.kode_brng')
            ->leftJoin('satu_sehat_medicationdispense_new', function ($join) {
                $join->on('satu_sehat_medicationdispense_new.no_rawat', '=', 'detail_pemberian_obat.no_rawat')
                    ->on('satu_sehat_medicationdispense_new.tgl_perawatan', '=', 'detail_pemberian_obat.tgl_perawatan')
                    ->on('satu_sehat_medicationdispense_new.jam', '=', 'detail_pemberian_obat.jam')
                    ->on('satu_sehat_medicationdispense_new.kode_brng', '=', 'detail_pemberian_obat.kode_brng')
                    ->on('satu_sehat_medicationdispense_new.no_batch', '=', 'detail_pemberian_obat.no_batch')
                    ->on('satu_sehat_medicationdispense_new.no_faktur', '=', 'detail_pemberian_obat.no_faktur');
            })
            ->where(function ($subQuery) use ($filters) {
                $subQuery->whereBetween('detail_pemberian_obat.tgl_perawatan', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('satu_sehat_medicationdispense_new.tanggal_kirim', [
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
                    ->orWhere('detail_pemberian_obat.kode_brng', 'like', $search)
                    ->orWhere('satu_sehat_mapping_obat.obat_display', 'like', $search);
            });
        }

        return $query
            ->select([
                'reg_periksa.tgl_registrasi', 'reg_periksa.jam_reg', 'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis', 'reg_periksa.status_lanjut', 'pasien.nm_pasien', 'pasien.no_ktp',
                'pegawai.nama', DB::raw('pegawai.no_ktp as ktp_praktisi'),
                'satu_sehat_encounter_new.id_encounter', 'satu_sehat_mapping_obat.obat_code',
                'satu_sehat_mapping_obat.obat_display', 'satu_sehat_mapping_obat.form_display',
                'satu_sehat_mapping_obat.route_display', 'detail_pemberian_obat.kode_brng',
                'detail_pemberian_obat.jml', 'detail_pemberian_obat.no_batch', 'detail_pemberian_obat.no_faktur',
                'detail_pemberian_obat.tgl_perawatan', 'detail_pemberian_obat.jam',
                'resep_obat.tgl_peresepan', 'resep_obat.jam_peresepan', 'resep_obat.no_resep',
                'satu_sehat_medication.id_medication', 'aturan_pakai.aturan',
                'satu_sehat_mapping_lokasi_depo_farmasi.id_lokasi_satusehat', 'bangsal.nm_bangsal',
                'satu_sehat_medicationdispense_new.id_medicationdispanse',
                DB::raw('satu_sehat_medicationdispense_new.status as dispense_status'),
            ])
            ->orderByDesc('detail_pemberian_obat.tgl_perawatan')
            ->orderByDesc('detail_pemberian_obat.jam')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                $status = strtolower((string) $row->dispense_status);
                $sent = $status === 'berhasil' && filled($row->id_medicationdispanse);

                return [
                    'waktu_pemberian' => $row->tgl_perawatan . 'T' . $row->jam . '+07:00',
                    'waktu_resep' => $row->tgl_peresepan . 'T' . $row->jam_peresepan . '+07:00',
                    'waktu_registrasi' => $row->tgl_registrasi . 'T' . $row->jam_reg . '+07:00',
                    'no_rawat' => $row->no_rawat,
                    'norm' => $row->no_rkm_medis,
                    'pasien' => $row->nm_pasien ?: '-',
                    'nik_pasien' => $row->no_ktp ?: '-',
                    'praktisi' => $row->nama ?: '-',
                    'nik_praktisi' => $row->ktp_praktisi ?: '-',
                    'id_encounter' => $row->id_encounter ?: '-',
                    'jenis_layanan' => strcasecmp((string) $row->status_lanjut, 'Ranap') === 0 ? 'Ranap' : 'Ralan',
                    'no_resep' => $row->no_resep ?: '-',
                    'kode_brng' => $row->kode_brng ?: '-',
                    'obat_display' => $row->obat_display ?: '-',
                    'jumlah' => $row->jml ?: '-',
                    'aturan_pakai' => $row->aturan ?: '-',
                    'no_batch' => $row->no_batch ?: '-',
                    'no_faktur' => $row->no_faktur ?: '-',
                    'depo' => $row->nm_bangsal ?: '-',
                    'id_lokasi' => $row->id_lokasi_satusehat ?: '-',
                    'id_medication' => $row->id_medication ?: '-',
                    'obat_code' => $row->obat_code ?: '-',
                    'form_display' => $row->form_display ?: '-',
                    'route_display' => $row->route_display ?: '-',
                    'id_medicationdispense' => $row->id_medicationdispanse ?: '-',
                    'status_medicationdispense' => match (true) {
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
}
