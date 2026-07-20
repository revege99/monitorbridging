<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SatuSehatObservationController extends Controller
{
    private const OBSERVATIONS = [
        'suhu' => ['label' => 'Suhu', 'field' => 'suhu_tubuh', 'table' => 'satu_sehat_observationttvsuhu_new', 'unit' => '°C'],
        'respirasi' => ['label' => 'Respirasi', 'field' => 'respirasi', 'table' => 'satu_sehat_observationttvrespirasi_new', 'unit' => 'x/menit'],
        'nadi' => ['label' => 'Nadi', 'field' => 'nadi', 'table' => 'satu_sehat_observationttvnadi_new', 'unit' => 'x/menit'],
        'spo2' => ['label' => 'SpO₂', 'field' => 'spo2', 'table' => 'satu_sehat_observationttvspo2_new', 'unit' => '%'],
        'gcs' => ['label' => 'GCS', 'field' => 'gcs', 'table' => 'satu_sehat_observationttvgcs_new', 'unit' => '-'],
        'kesadaran' => ['label' => 'Kesadaran', 'field' => 'kesadaran', 'table' => 'satu_sehat_observationttvkesadaran_new', 'unit' => '-'],
        'tensi' => ['label' => 'Tensi', 'field' => 'tensi', 'table' => 'satu_sehat_observationttvtensi_new', 'unit' => 'mmHg'],
        'tinggi-badan' => ['label' => 'Tinggi Badan', 'field' => 'tinggi', 'table' => 'satu_sehat_observationttvtinggi_new', 'unit' => 'cm'],
        'berat-badan' => ['label' => 'Berat Badan', 'field' => 'berat', 'table' => 'satu_sehat_observationttvberat_new', 'unit' => 'kg'],
        'lingkar-perut' => ['label' => 'Lingkar Perut', 'field' => 'lingkar_perut', 'table' => 'satu_sehat_observationttvlingkarperut_new', 'unit' => 'cm', 'ranap' => false],
    ];

    public function index(Request $request)
    {
        $today = now()->toDateString();
        $activeTab = (string) $request->query('tab', 'suhu');
        $activeTab = array_key_exists($activeTab, self::OBSERVATIONS) ? $activeTab : 'suhu';
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'start_date' => $request->query('start_date', $today) ?: $today,
            'end_date' => $request->query('end_date', $today) ?: $today,
        ];
        $dbError = null;

        try {
            $rows = $this->observationRows(self::OBSERVATIONS[$activeTab], $filters);
        } catch (Throwable $exception) {
            $dbError = $exception->getMessage();
            $rows = collect();
        }

        return view('satu-sehat-encounter', [
            'resourceType' => 'observation',
            'observationTabs' => collect(self::OBSERVATIONS)->mapWithKeys(
                fn (array $config, string $key) => [$key => $config['label']]
            )->all(),
            'activeObservationTab' => $activeTab,
            'rows' => $rows,
            'dbError' => $dbError,
            'usingFallback' => $dbError !== null,
            'filters' => $filters,
            'pageDateLabel' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    private function observationRows(array $config, array $filters): Collection
    {
        $rawatJalan = $this->baseQuery('pemeriksaan_ralan', 'nota_jalan', 'Ralan', $config, $filters);
        $combinedQuery = $rawatJalan;

        if ($config['ranap'] ?? true) {
            $rawatInap = $this->baseQuery('pemeriksaan_ranap', 'nota_inap', 'Ranap', $config, $filters);
            $combinedQuery->unionAll($rawatInap);
        }

        return DB::query()
            ->fromSub($combinedQuery, 'observation_rows')
            ->orderByDesc('tgl_perawatan')
            ->orderByDesc('jam_rawat')
            ->limit(500)
            ->get()
            ->map(function ($row) use ($config) {
                $sent = $row->observation_status === 'berhasil' && filled($row->observation_id);

                return [
                    'waktu_pemeriksaan' => $row->tgl_perawatan . 'T' . $row->jam_rawat . '+07:00',
                    'waktu_registrasi' => $row->tgl_registrasi . 'T' . $row->jam_reg . '+07:00',
                    'no_rawat' => $row->no_rawat,
                    'norm' => $row->no_rkm_medis,
                    'pasien' => $row->nm_pasien ?: '-',
                    'nik_pasien' => $row->no_ktp ?: '-',
                    'stts' => $row->stts ?: '-',
                    'jenis_layanan' => $row->jenis_layanan,
                    'pulang' => $row->pulang ?: '-',
                    'id_encounter' => $row->id_encounter ?: '-',
                    'nilai' => $row->nilai_observation,
                    'satuan' => $config['unit'],
                    'praktisi' => $row->nama ?: '-',
                    'nik_praktisi' => $row->ktp_praktisi ?: '-',
                    'id_observation' => $row->observation_id ?: '-',
                    'status_observation' => match (true) {
                        $sent => 'Sudah Terkirim',
                        $row->observation_status === 'gagal' => 'Gagal',
                        default => 'Belum Terkirim',
                    },
                    'warna' => match (true) {
                        $sent => 'emerald',
                        $row->observation_status === 'gagal' => 'rose',
                        default => 'amber',
                    },
                ];
            });
    }

    private function baseQuery(
        string $examinationTable,
        string $notaTable,
        string $serviceType,
        array $config,
        array $filters
    ): Builder {
        $resultTable = $config['table'];
        $valueField = $config['field'];

        $query = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->leftJoin($notaTable, $notaTable . '.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('satu_sehat_encounter_new', 'satu_sehat_encounter_new.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join($examinationTable, $examinationTable . '.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pegawai', $examinationTable . '.nip', '=', 'pegawai.nik')
            ->leftJoin($resultTable, $resultTable . '.no_rawat', '=', $examinationTable . '.no_rawat')
            ->whereNotNull($examinationTable . '.' . $valueField)
            ->where($examinationTable . '.' . $valueField, '<>', '')
            ->where(function ($query) use ($examinationTable, $resultTable, $filters) {
                $query->whereBetween($examinationTable . '.tgl_perawatan', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween($resultTable . '.created_at', [
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
                DB::raw("'{$serviceType}' as jenis_layanan"),
                DB::raw("CONCAT({$notaTable}.tanggal, 'T', {$notaTable}.jam, '+07:00') as pulang"),
                'satu_sehat_encounter_new.id_encounter',
                'pegawai.nama',
                DB::raw('pegawai.no_ktp as ktp_praktisi'),
                $examinationTable . '.tgl_perawatan',
                $examinationTable . '.jam_rawat',
                DB::raw("{$examinationTable}.{$valueField} as nilai_observation"),
                DB::raw("IFNULL({$resultTable}.observation_id, '') as observation_id"),
                DB::raw("{$resultTable}.status as observation_status"),
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
                    ->orWhere('reg_periksa.stts', 'like', $search);
            });
        }

        return $query;
    }
}
