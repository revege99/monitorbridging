<?php

namespace App\Services\Pdf;

use App\Models\Clinic;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class HealthCertificateService
{
    public function data(string $noRawat, ?string $noSurat, Clinic $profile): array
    {
        $db = DB::connection('clinic');
        $query = $db->table('surat_keterangan_sehat as sks')
            ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'sks.no_rawat')
            ->join('pasien as p', 'p.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->leftJoin('dokter as d', 'd.kd_dokter', '=', 'rp.kd_dokter')
            ->where('sks.no_rawat', $noRawat);
        if (filled($noSurat)) {
            $query->where('sks.no_surat', $noSurat);
        }
        $letter = $query->orderByDesc('sks.tanggalsurat')->orderByDesc('sks.no_surat')->first([
            'sks.no_surat', 'sks.tanggalsurat', 'sks.berat', 'sks.tinggi', 'sks.tensi', 'sks.suhu', 'sks.butawarna', 'sks.keperluan', 'sks.kesimpulan',
            'p.nm_pasien', 'p.tmp_lahir', 'p.tgl_lahir', 'p.jk', 'p.alamat', 'p.kelurahanpj', 'p.kecamatanpj', 'p.kabupatenpj', 'd.nm_dokter',
        ]);
        abort_if($letter === null, 404, 'Data surat keterangan sehat tidak ditemukan.');

        $setting = $db->table('setting')->orderByRaw("CASE WHEN aktifkan = 'Yes' THEN 0 ELSE 1 END")->first([
            'yayasan', 'nama_instansi', 'alamat_instansi', 'kabupaten', 'propinsi', 'kontak', 'email',
        ]);
        abort_if($setting === null || blank($setting->nama_instansi), 503, 'Identitas fasilitas pada tabel setting belum tersedia.');

        $date = $this->date($letter->tanggalsurat);

        return [
            'letter_number' => $this->value($letter->no_surat),
            'legal_name' => $this->value($setting->yayasan, $setting->nama_instansi),
            'facility_name' => $this->value($setting->nama_instansi),
            'facility_address' => collect([$setting->alamat_instansi, $setting->kabupaten, $setting->propinsi])->filter()->join(', '),
            'facility_contact' => $this->contact($setting->kontak, $setting->email),
            'logo_path' => $profile->logo_path,
            'doctor' => $this->value($letter->nm_dokter),
            'patient_name' => $this->value($letter->nm_pasien),
            'birth' => collect([$this->value($letter->tmp_lahir), $this->indonesianDate($letter->tgl_lahir)])->filter(fn ($value) => $value !== '-')->join(', ') ?: '-',
            'sex' => $letter->jk === 'L' ? 'Laki-laki' : ($letter->jk === 'P' ? 'Perempuan' : '-'),
            'address' => collect([$letter->alamat, $letter->kelurahanpj, $letter->kecamatanpj, $letter->kabupatenpj])->filter()->unique()->join(', ') ?: '-',
            'height' => $this->unit($letter->tinggi, 'cm'),
            'weight' => $this->unit($letter->berat, 'kg'),
            'blood_pressure' => $this->unit($letter->tensi, 'mmHg'),
            'temperature' => $this->unit($letter->suhu, '°C'),
            'color_blindness' => $this->value($letter->butawarna),
            'purpose' => $this->value($letter->keperluan),
            'conclusion' => $this->value($letter->kesimpulan),
            'letter_date' => $this->indonesianDate($date?->format('Y-m-d')),
            'letter_city' => $this->value($setting->kabupaten),
        ];
    }

    private function value(?string ...$values): string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return trim($value);
            }
        }

        return '-';
    }

    private function date(?string $value): ?CarbonImmutable
    {
        return filled($value) && $value !== '0000-00-00' ? CarbonImmutable::createFromFormat('!Y-m-d', $value) : null;
    }

    private function unit(?string $value, string $unit): string
    {
        $value = trim((string) $value);

        return $value === '' || $value === '-' ? '-' : $value.' '.$unit;
    }

    private function contact(?string $phone, ?string $email): string
    {
        $values = [];
        if (filled($phone)) {
            $values[] = 'Hp: '.trim($phone);
        } if (filled($email)) {
            $values[] = 'Email: '.trim($email);
        }

        return implode(' | ', $values);
    }

    private function indonesianDate(?string $value): string
    {
        $date = $this->date($value);
        if (! $date) {
            return '-';
        } $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return $date->day.' '.$months[$date->month].' '.$date->year;
    }
}
