<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SatuSehatMedicationStatementController extends Controller
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
            $rows = $this->medicationStatementRows($filters);
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
            $rows = collect();
        }

        return view('satu-sehat-encounter', [
            'resourceType' => 'medication-statement',
            'rows' => $rows,
            'dbError' => $dbError,
            'usingFallback' => $dbError !== null,
            'filters' => $filters,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    private function medicationStatementRows(array $filters): Collection
    {
        $regular = $this->regularQuery($filters);
        $compound = $this->compoundQuery($filters);

        return DB::query()
            ->fromSub($regular->unionAll($compound), 'medication_statement_rows')
            ->orderByDesc('tgl_penyerahan')
            ->orderByDesc('jam_penyerahan')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                $status = strtolower((string) $row->statement_status);
                $sent = $status === 'berhasil' && filled($row->id_medicationstatement);

                return [
                    'waktu_registrasi' => $row->tgl_registrasi . 'T' . $row->jam_reg . '+07:00',
                    'waktu_penyerahan' => $row->tgl_penyerahan . 'T' . $row->jam_penyerahan . '+07:00',
                    'no_rawat' => $row->no_rawat,
                    'norm' => $row->no_rkm_medis,
                    'pasien' => $row->nm_pasien ?: '-',
                    'nik_pasien' => $row->no_ktp ?: '-',
                    'praktisi' => $row->nama ?: '-',
                    'nik_praktisi' => $row->ktp_praktisi ?: '-',
                    'id_encounter' => $row->id_encounter ?: '-',
                    'jenis_layanan' => strcasecmp((string) $row->status_lanjut, 'Ranap') === 0 ? 'Ranap' : 'Ralan',
                    'jenis_resep' => $row->jenis_resep,
                    'no_resep' => $row->no_resep ?: '-',
                    'no_racik' => $row->no_racik ?: '-',
                    'kode_brng' => $row->kode_brng ?: '-',
                    'obat_display' => $row->obat_display ?: '-',
                    'jumlah' => $row->jumlah ?: '-',
                    'aturan_pakai' => $row->aturan_pakai ?: '-',
                    'id_medication' => $row->id_medication ?: '-',
                    'obat_code' => $row->obat_code ?: '-',
                    'form_display' => $row->form_display ?: '-',
                    'route_display' => $row->route_display ?: '-',
                    'id_medicationstatement' => $row->id_medicationstatement ?: '-',
                    'status_medicationstatement' => match (true) {
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

    private function regularQuery(array $filters): Builder
    {
        $query = $this->baseQuery()
            ->join('resep_dokter', 'resep_dokter.no_resep', '=', 'resep_obat.no_resep')
            ->leftJoin('satu_sehat_mapping_obat', 'satu_sehat_mapping_obat.kode_brng', '=', 'resep_dokter.kode_brng')
            ->leftJoin('satu_sehat_medication', 'satu_sehat_medication.kode_brng', '=', 'resep_dokter.kode_brng')
            ->leftJoin('satu_sehat_medicationstatement_new', function ($join) {
                $join->on('satu_sehat_medicationstatement_new.no_rawat', '=', 'reg_periksa.no_rawat')
                    ->on('satu_sehat_medicationstatement_new.no_resep', '=', 'resep_dokter.no_resep')
                    ->on('satu_sehat_medicationstatement_new.kode_brng', '=', 'resep_dokter.kode_brng');
            });

        $this->applyFilters($query, 'resep_dokter.kode_brng', $filters);

        return $query->select($this->selectColumns(
            'resep_dokter.kode_brng', 'resep_dokter.jml', 'resep_dokter.aturan_pakai', "''", "'Non Racikan'"
        ));
    }

    private function compoundQuery(array $filters): Builder
    {
        $query = $this->baseQuery()
            ->join('resep_dokter_racikan', 'resep_dokter_racikan.no_resep', '=', 'resep_obat.no_resep')
            ->join('resep_dokter_racikan_detail', function ($join) {
                $join->on('resep_dokter_racikan_detail.no_resep', '=', 'resep_dokter_racikan.no_resep')
                    ->on('resep_dokter_racikan_detail.no_racik', '=', 'resep_dokter_racikan.no_racik');
            })
            ->leftJoin('satu_sehat_mapping_obat', 'satu_sehat_mapping_obat.kode_brng', '=', 'resep_dokter_racikan_detail.kode_brng')
            ->leftJoin('satu_sehat_medication', 'satu_sehat_medication.kode_brng', '=', 'resep_dokter_racikan_detail.kode_brng')
            ->leftJoin('satu_sehat_medicationstatement_new', function ($join) {
                $join->on('satu_sehat_medicationstatement_new.no_rawat', '=', 'reg_periksa.no_rawat')
                    ->on('satu_sehat_medicationstatement_new.no_resep', '=', 'resep_dokter_racikan_detail.no_resep')
                    ->on('satu_sehat_medicationstatement_new.kode_brng', '=', 'resep_dokter_racikan_detail.kode_brng');
            });

        $this->applyFilters($query, 'resep_dokter_racikan_detail.kode_brng', $filters);

        return $query->select($this->selectColumns(
            'resep_dokter_racikan_detail.kode_brng', 'resep_dokter_racikan_detail.jml',
            'resep_dokter_racikan.aturan_pakai', 'resep_dokter_racikan_detail.no_racik', "'Racikan'"
        ));
    }

    private function baseQuery(): Builder
    {
        return DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('resep_obat', 'resep_obat.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('pegawai', 'resep_obat.kd_dokter', '=', 'pegawai.nik')
            ->leftJoin('satu_sehat_encounter_new', 'satu_sehat_encounter_new.no_rawat', '=', 'reg_periksa.no_rawat')
            ->whereNotNull('resep_obat.tgl_penyerahan')
            ->where('resep_obat.tgl_penyerahan', '<>', '0000-00-00');
    }

    private function applyFilters(Builder $query, string $itemColumn, array $filters): void
    {
        $query->where(function ($subQuery) use ($filters) {
            $subQuery->whereBetween('resep_obat.tgl_penyerahan', [$filters['start_date'], $filters['end_date']])
                ->orWhereBetween('satu_sehat_medicationstatement_new.tanggal_kirim', [
                    $filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59',
                ]);
        });

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($subQuery) use ($search, $itemColumn) {
                $subQuery->where('reg_periksa.no_rawat', 'like', $search)
                    ->orWhere('reg_periksa.no_rkm_medis', 'like', $search)
                    ->orWhere('pasien.nm_pasien', 'like', $search)
                    ->orWhere('pasien.no_ktp', 'like', $search)
                    ->orWhere($itemColumn, 'like', $search)
                    ->orWhere('satu_sehat_mapping_obat.obat_display', 'like', $search);
            });
        }
    }

    private function selectColumns(string $item, string $quantity, string $instruction, string $compound, string $type): array
    {
        return [
            'reg_periksa.tgl_registrasi', 'reg_periksa.jam_reg', 'reg_periksa.no_rawat',
            'reg_periksa.no_rkm_medis', 'reg_periksa.status_lanjut', 'pasien.nm_pasien', 'pasien.no_ktp',
            'pegawai.nama', DB::raw('pegawai.no_ktp as ktp_praktisi'), 'satu_sehat_encounter_new.id_encounter',
            'satu_sehat_mapping_obat.obat_code', 'satu_sehat_mapping_obat.obat_display',
            'satu_sehat_mapping_obat.form_display', 'satu_sehat_mapping_obat.route_display',
            'resep_obat.tgl_penyerahan', 'resep_obat.jam_penyerahan', DB::raw("{$item} as kode_brng"),
            DB::raw("{$quantity} as jumlah"), DB::raw("{$instruction} as aturan_pakai"), 'resep_obat.no_resep',
            'satu_sehat_medication.id_medication', 'satu_sehat_medicationstatement_new.id_medicationstatement',
            DB::raw('satu_sehat_medicationstatement_new.status as statement_status'),
            DB::raw("{$compound} as no_racik"), DB::raw("{$type} as jenis_resep"),
        ];
    }
}
