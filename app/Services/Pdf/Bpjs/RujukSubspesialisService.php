<?php

namespace App\Services\Pdf\Bpjs;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class RujukSubspesialisService
{
    public function data(string $noRawat): array
    {
        $db = DB::connection('clinic');
        $referral = $db->table('pcare_rujuk_subspesialis as prs')
            ->join('pasien as p', 'p.no_rkm_medis', '=', 'prs.no_rkm_medis')
            ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'prs.no_rawat')
            ->join('dokter as d', 'd.kd_dokter', '=', 'rp.kd_dokter')
            ->where('prs.no_rawat', $noRawat)
            ->first([
                'prs.no_rawat', 'prs.noKunjungan', 'prs.noKartu', 'prs.tglDaftar',
                'prs.nmSubSpesialis', 'prs.nmPPK', 'prs.nmDiag1', 'prs.alasanTACC',
                'prs.jadwalFaskes', 'p.nm_pasien', 'p.tgl_lahir', 'p.jk',
                'rp.umurdaftar', 'rp.sttsumur', 'd.nm_dokter',
            ]);

        abort_if($referral === null, 404, 'Data rujukan subspesialis atau pasien tidak ditemukan.');

        $setting = $db->table('setting')
            ->orderByRaw("CASE WHEN aktifkan = 'Yes' THEN 0 ELSE 1 END")
            ->orderBy('nama_instansi')
            ->first(['nama_instansi', 'kabupaten']);
        abort_if($setting === null || trim((string) $setting->nama_instansi) === '', 503,
            'Identitas fasilitas pada tabel setting belum tersedia.');

        $referralDate = $this->date($referral->tglDaftar);
        $validUntil = $referralDate?->addDays(89);

        return [
            'referral_number' => $this->value($referral->noKunjungan),
            'facility' => $this->value($setting->nama_instansi),
            'district' => $this->value($setting->kabupaten),
            'subspecialty' => $this->value($referral->nmSubSpesialis),
            'destination' => $this->value($referral->nmPPK),
            'patient_name' => $this->value($referral->nm_pasien),
            'age' => $this->value($referral->umurdaftar, $referral->sttsumur),
            'birth_date' => $this->indonesianDate($referral->tgl_lahir),
            'card_number' => $this->value($referral->noKartu),
            'sex' => match ($referral->jk) {
                'L' => 'L', 'P' => 'P', default => $this->value($referral->jk),
            },
            'diagnosis' => $this->value($referral->nmDiag1),
            'tacc_reason' => $this->value($referral->alasanTACC),
            'schedule' => $this->value($referral->jadwalFaskes),
            'referral_date' => $referralDate?->format('d/m/Y') ?? '-',
            'valid_until' => $this->indonesianDate($validUntil?->format('Y-m-d')),
            'doctor' => $this->value($referral->nm_dokter),
            'printed_at' => now('Asia/Jakarta')->format('d/m/Y H:i:s'),
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

    private function indonesianDate(?string $value): string
    {
        $date = $this->date($value);
        if (! $date) {
            return '-';
        }

        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        return $date->day.' '.$months[$date->month].' '.$date->year;
    }
}
