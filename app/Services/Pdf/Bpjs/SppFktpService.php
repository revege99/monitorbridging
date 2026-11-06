<?php

namespace App\Services\Pdf\Bpjs;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SppFktpService
{
    public function data(string $noRawat): array
    {
        $db = DB::connection('clinic');
        $visit = $db->table('reg_periksa as rp')
            ->join('pasien as p', 'p.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->leftJoin('pcare_pendaftaran as pp', 'pp.no_rawat', '=', 'rp.no_rawat')
            ->where('rp.no_rawat', $noRawat)
            ->first([
                'rp.no_rawat', 'rp.no_rkm_medis', 'rp.tgl_registrasi', 'rp.status_lanjut',
                'p.nm_pasien', 'p.no_ktp', 'p.jk', 'p.tgl_lahir', 'p.no_tlp',
                'p.alamat', 'p.no_peserta', 'pp.noKartu', 'pp.tglDaftar', 'pp.kdTkp',
            ]);

        abort_if($visit === null, 404, 'Data kunjungan atau pasien tidak ditemukan.');

        $facility = $db->table('setting')
            ->orderByRaw("CASE WHEN aktifkan = 'Yes' THEN 0 ELSE 1 END")
            ->orderBy('nama_instansi')->first(['kode_ppk', 'nama_instansi']);
        abort_if($facility === null || trim((string) $facility->nama_instansi) === '', 503,
            'Identitas fasilitas pada tabel setting belum tersedia.');

        $actions = $db->table('pcare_tindakan_ralan_diberikan as t')
            ->leftJoin('jns_perawatan as j', 'j.kd_jenis_prw', '=', 't.kd_jenis_prw')
            ->where('t.no_rawat', $noRawat)
            ->orderBy('t.tgl_perawatan')->orderBy('t.jam')->orderBy('t.kd_jenis_prw')
            ->get(['t.noKunjungan', 't.kd_jenis_prw', 'j.nm_perawatan']);

        $visitNumbers = $actions->pluck('noKunjungan')
            ->map(fn ($number) => trim((string) $number))
            ->filter(fn ($number) => $number !== '' && $number !== '0')->unique()->values();

        if ($visitNumbers->count() > 1) {
            throw ValidationException::withMessages([
                'no_rawat' => 'Terdapat lebih dari satu nomor kunjungan PCare. Periksa data tindakan sebelum mencetak.',
            ]);
        }

        $serviceDate = $this->date($visit->tglDaftar) ?? $this->date($visit->tgl_registrasi);
        $birthDate = $this->date($visit->tgl_lahir);
        $age = '-';
        if ($birthDate && $serviceDate && $birthDate->lessThanOrEqualTo($serviceDate)) {
            $interval = $birthDate->diff($serviceDate);
            $age = "{$interval->y} tahun {$interval->m} bulan {$interval->d} hari";
        }

        return [
            'facility' => implode(' - ', array_filter([
                trim((string) $facility->kode_ppk), trim($facility->nama_instansi),
            ], fn ($value) => $value !== '')),
            'visit_number' => $visitNumbers->first() ?? '-',
            'medical_record' => $visit->no_rkm_medis,
            'card_number' => $this->value($visit->noKartu, $visit->no_peserta),
            'nik' => $this->value($visit->no_ktp),
            'patient_name' => $this->value($visit->nm_pasien),
            'sex' => match ($visit->jk) {
                'L' => 'Laki-laki', 'P' => 'Perempuan', default => '-'
            },
            'phone' => $this->value($visit->no_tlp),
            'birth_date' => $birthDate?->format('d/m/Y') ?? '-',
            'age' => $age,
            'service_date' => $serviceDate?->format('d/m/Y') ?? '-',
            'service_type' => match (substr((string) $visit->kdTkp, 0, 2)) {
                '10' => 'RJTP', '20' => 'RITP', '50' => 'Promotif Preventif',
                default => $visit->status_lanjut === 'Ranap' ? 'RITP' : 'RJTP',
            },
            'address' => $this->value($visit->alamat),
            'services' => $actions->unique('kd_jenis_prw')->map(
                fn ($action) => $this->value($action->nm_perawatan, $action->kd_jenis_prw)
            )->values()->all(),
        ];
    }

    private function value(?string ...$values): string
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return trim($value);
            }
        }

        return '-';
    }

    private function date(?string $value): ?CarbonImmutable
    {
        if (! $value || $value === '0000-00-00') {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $value);
    }
}
