<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SatuSehatMedicationRequestController extends Controller
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
            $rows = $this->medicationRequestRows($filters);
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
            $rows = collect();
        }

        return view('satu-sehat-encounter', [
            'resourceType' => 'medication-request',
            'rows' => $rows,
            'dbError' => $dbError,
            'usingFallback' => $dbError !== null,
            'filters' => $filters,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    private function medicationRequestRows(array $filters): Collection
    {
        $regular = $this->regularQuery($filters);
        $compound = $this->compoundQuery($filters);

        return DB::query()
            ->fromSub($regular->unionAll($compound), 'medication_request_rows')
            ->orderByDesc('tgl_peresepan')
            ->orderByDesc('jam_peresepan')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                $sent = $row->request_status === 'berhasil' && filled($row->id_medicationrequest);

                return [
                    'waktu_registrasi' => $row->tgl_registrasi . 'T' . $row->jam_reg . '+07:00',
                    'waktu_resep' => $row->tgl_peresepan . 'T' . $row->jam_peresepan . '+07:00',
                    'no_rawat' => $row->no_rawat,
                    'norm' => $row->no_rkm_medis,
                    'pasien' => $row->nm_pasien ?: '-',
                    'nik_pasien' => $row->no_ktp ?: '-',
                    'praktisi' => $row->nama ?: '-',
                    'nik_praktisi' => $row->ktp_praktisi ?: '-',
                    'id_encounter' => $row->id_encounter ?: '-',
                    'obat_code' => $row->obat_code ?: '-',
                    'obat_system' => $row->obat_system ?: '-',
                    'kode_brng' => $row->kode_brng ?: '-',
                    'obat_display' => $row->obat_display ?: '-',
                    'form_code' => $row->form_code ?: '-',
                    'form_system' => $row->form_system ?: '-',
                    'form_display' => $row->form_display ?: '-',
                    'route_code' => $row->route_code ?: '-',
                    'route_system' => $row->route_system ?: '-',
                    'route_display' => $row->route_display ?: '-',
                    'denominator_code' => $row->denominator_code ?: '-',
                    'denominator_system' => $row->denominator_system ?: '-',
                    'jumlah' => $row->jumlah ?: '-',
                    'id_medication' => $row->id_medication ?: '-',
                    'aturan_pakai' => $row->aturan_pakai ?: '-',
                    'no_resep' => $row->no_resep ?: '-',
                    'no_racik' => $row->no_racik ?: '-',
                    'jenis_resep' => $row->jenis_resep,
                    'jenis_layanan' => strcasecmp((string) $row->status_lanjut, 'Ranap') === 0 ? 'Ranap' : 'Ralan',
                    'id_medicationrequest' => $row->id_medicationrequest ?: '-',
                    'status_medicationrequest' => match (true) {
                        $sent => 'Sudah Terkirim',
                        $row->request_status === 'gagal' => 'Gagal',
                        default => 'Belum Terkirim',
                    },
                    'warna' => match (true) {
                        $sent => 'emerald',
                        $row->request_status === 'gagal' => 'rose',
                        default => 'amber',
                    },
                ];
            });
    }

    private function regularQuery(array $filters): Builder
    {
        $query = $this->baseQuery($filters)
            ->join('resep_dokter', 'resep_dokter.no_resep', '=', 'resep_obat.no_resep')
            ->leftJoin('satu_sehat_mapping_obat', 'satu_sehat_mapping_obat.kode_brng', '=', 'resep_dokter.kode_brng')
            ->leftJoin('satu_sehat_medication', 'satu_sehat_medication.kode_brng', '=', 'resep_dokter.kode_brng')
            ->leftJoin('satu_sehat_medicationrequest_new', function ($join) {
                $join->on('satu_sehat_medicationrequest_new.no_resep', '=', 'resep_dokter.no_resep')
                    ->on('satu_sehat_medicationrequest_new.kode_brng', '=', 'resep_dokter.kode_brng');
            });

        $this->applyDateAndSearch($query, 'resep_dokter.kode_brng', $filters);

        return $query->select($this->selectColumns(
            'resep_dokter.kode_brng',
            'resep_dokter.jml',
            'resep_dokter.aturan_pakai',
            "''",
            "'Non Racikan'"
        ));
    }

    private function compoundQuery(array $filters): Builder
    {
        $query = $this->baseQuery($filters)
            ->join('resep_dokter_racikan', 'resep_dokter_racikan.no_resep', '=', 'resep_obat.no_resep')
            ->join('resep_dokter_racikan_detail', function ($join) {
                $join->on('resep_dokter_racikan_detail.no_resep', '=', 'resep_dokter_racikan.no_resep')
                    ->on('resep_dokter_racikan_detail.no_racik', '=', 'resep_dokter_racikan.no_racik');
            })
            ->leftJoin('satu_sehat_mapping_obat', 'satu_sehat_mapping_obat.kode_brng', '=', 'resep_dokter_racikan_detail.kode_brng')
            ->leftJoin('satu_sehat_medication', 'satu_sehat_medication.kode_brng', '=', 'resep_dokter_racikan_detail.kode_brng')
            ->leftJoin('satu_sehat_medicationrequest_new', function ($join) {
                $join->on('satu_sehat_medicationrequest_new.no_resep', '=', 'resep_dokter_racikan_detail.no_resep')
                    ->on('satu_sehat_medicationrequest_new.kode_brng', '=', 'resep_dokter_racikan_detail.kode_brng');
            });

        $this->applyDateAndSearch($query, 'resep_dokter_racikan_detail.kode_brng', $filters);

        return $query->select($this->selectColumns(
            'resep_dokter_racikan_detail.kode_brng',
            'resep_dokter_racikan_detail.jml',
            'resep_dokter_racikan.aturan_pakai',
            'resep_dokter_racikan_detail.no_racik',
            "'Racikan'"
        ));
    }

    private function baseQuery(array $filters): Builder
    {
        return DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('resep_obat', 'resep_obat.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pegawai', 'resep_obat.kd_dokter', '=', 'pegawai.nik')
            ->leftJoin('satu_sehat_encounter_new', 'satu_sehat_encounter_new.no_rawat', '=', 'reg_periksa.no_rawat');
    }

    private function applyDateAndSearch(Builder $query, string $itemColumn, array $filters): void
    {
        $query->where(function ($subQuery) use ($filters) {
            $subQuery->whereBetween('resep_obat.tgl_peresepan', [$filters['start_date'], $filters['end_date']])
                ->orWhereBetween('satu_sehat_medicationrequest_new.created_at', [
                    $filters['start_date'] . ' 00:00:00',
                    $filters['end_date'] . ' 23:59:59',
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

    private function selectColumns(
        string $itemColumn,
        string $quantityColumn,
        string $instructionColumn,
        string $compoundNumberExpression,
        string $recipeTypeExpression
    ): array {
        return [
            'reg_periksa.tgl_registrasi',
            'reg_periksa.jam_reg',
            'reg_periksa.no_rawat',
            'reg_periksa.no_rkm_medis',
            'pasien.nm_pasien',
            'pasien.no_ktp',
            'reg_periksa.status_lanjut',
            'pegawai.nama',
            DB::raw('pegawai.no_ktp as ktp_praktisi'),
            DB::raw("IFNULL(satu_sehat_encounter_new.id_encounter, '') as id_encounter"),
            'satu_sehat_mapping_obat.obat_code',
            'satu_sehat_mapping_obat.obat_system',
            DB::raw("{$itemColumn} as kode_brng"),
            'satu_sehat_mapping_obat.obat_display',
            'satu_sehat_mapping_obat.form_code',
            'satu_sehat_mapping_obat.form_system',
            'satu_sehat_mapping_obat.form_display',
            'satu_sehat_mapping_obat.route_code',
            'satu_sehat_mapping_obat.route_system',
            'satu_sehat_mapping_obat.route_display',
            'satu_sehat_mapping_obat.denominator_code',
            'satu_sehat_mapping_obat.denominator_system',
            'resep_obat.tgl_peresepan',
            'resep_obat.jam_peresepan',
            DB::raw("{$quantityColumn} as jumlah"),
            DB::raw("IFNULL(satu_sehat_medication.id_medication, '') as id_medication"),
            DB::raw("{$instructionColumn} as aturan_pakai"),
            'resep_obat.no_resep',
            DB::raw("IFNULL(satu_sehat_medicationrequest_new.id_medicationrequest, '') as id_medicationrequest"),
            DB::raw('satu_sehat_medicationrequest_new.status as request_status'),
            DB::raw("{$compoundNumberExpression} as no_racik"),
            DB::raw("{$recipeTypeExpression} as jenis_resep"),
        ];
    }
}
