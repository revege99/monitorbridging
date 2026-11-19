<?php

namespace App\Services\Pdf;

use App\Models\Clinic;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SickCertificateService
{
    public function data(string $noRawat, ?string $noSurat, Clinic $profile): array
    {
        $db = DB::connection('clinic');
        $query = $db->table('suratsakit as ss')
            ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'ss.no_rawat')
            ->join('pasien as p', 'p.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->leftJoin('dokter as d', 'd.kd_dokter', '=', 'rp.kd_dokter')
            ->where('ss.no_rawat', $noRawat);

        if (filled($noSurat)) {
            $query->where('ss.no_surat', $noSurat);
        }

        $letter = $query->orderByDesc('ss.tanggalawal')->orderByDesc('ss.no_surat')->first([
            'ss.no_surat', 'ss.tanggalawal', 'ss.tanggalakhir', 'ss.lamasakit',
            'rp.umurdaftar', 'rp.sttsumur', 'p.nm_pasien', 'p.jk', 'p.pekerjaan',
            'p.alamat', 'p.kelurahanpj', 'p.kecamatanpj', 'p.kabupatenpj', 'p.propinsipj',
            'd.nm_dokter',
        ]);
        abort_if($letter === null, 404, 'Data surat keterangan sakit tidak ditemukan.');

        $setting = $db->table('setting')->orderByRaw("CASE WHEN aktifkan = 'Yes' THEN 0 ELSE 1 END")->first([
            'yayasan', 'nama_instansi', 'alamat_instansi', 'kabupaten', 'propinsi', 'kontak', 'email',
        ]);
        abort_if($setting === null || blank($setting->nama_instansi), 503, 'Identitas fasilitas pada tabel setting belum tersedia.');

        $start = $this->date($letter->tanggalawal);
        $end = $this->date($letter->tanggalakhir);

        return [
            'letter_number' => $this->value($letter->no_surat),
            'legal_name' => $this->value($setting->yayasan, $setting->nama_instansi),
            'facility_name' => $this->value($setting->nama_instansi),
            'facility_address' => collect([$setting->alamat_instansi, $setting->kabupaten, $setting->propinsi])->filter()->join(', '),
            'facility_contact' => $this->contact($setting->kontak, $setting->email),
            'logo_path' => $profile->logo_path,
            'patient_name' => $this->value($letter->nm_pasien),
            'age' => $this->age($letter->umurdaftar, $letter->sttsumur),
            'sex' => match ($letter->jk) {
                'L' => 'Laki-laki', 'P' => 'Perempuan', default => '-'
            },
            'occupation' => $this->value($letter->pekerjaan),
            'address' => collect([$letter->alamat, $letter->kelurahanpj, $letter->kecamatanpj, $letter->kabupatenpj, $letter->propinsipj])->filter()->unique()->join(', ') ?: '-',
            'duration' => $this->duration($letter->lamasakit, $start, $end),
            'start_date' => $this->indonesianDate($start),
            'end_date' => $this->indonesianDate($end),
            'letter_city' => $this->value($setting->kabupaten),
            'letter_date' => $this->indonesianDate($start),
            'doctor' => $this->value($letter->nm_dokter),
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

    private function age(?string $age, ?string $unit): string
    {
        $unit = match ($unit) {
            'Th' => 'Tahun', 'Bl' => 'Bulan', 'Hr' => 'Hari', default => ''
        };

        return filled($age) ? trim($age.' '.$unit) : '-';
    }

    private function duration(?string $duration, ?CarbonImmutable $start, ?CarbonImmutable $end): string
    {
        if (filled($duration)) {
            return trim($duration);
        }

        return $start && $end && $end->greaterThanOrEqualTo($start) ? (string) $start->diffInDays($end->addDay()) : '-';
    }

    private function contact(?string $phone, ?string $email): string
    {
        return collect([
            filled($phone) ? 'Hp: '.trim($phone) : null,
            filled($email) ? 'Email: '.trim($email) : null,
        ])->filter()->join(' | ');
    }

    private function indonesianDate(?CarbonImmutable $date): string
    {
        if (! $date) {
            return '-';
        }

        $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return $date->day.' '.$months[$date->month].' '.$date->year;
    }
}
