<?php

namespace App\Http\Controllers;

use App\Services\ActiveInstallation;
use App\Services\BpjsParticipantService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class KioskController extends Controller
{
    public function __construct(private readonly ActiveInstallation $active, private readonly BpjsParticipantService $participants)
    {
    }

    public function index(): View
    {
        $installation = $this->active->resolve();
        return view('kiosk.index', ['clinic' => $installation->clinic]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $numberRules = $request->input('jenis') === 'nik' ? ['digits:16'] : ['digits:13'];
        $data = $request->validate([
            'jenis' => ['required', Rule::in(['nik', 'noka'])],
            'nomor' => ['required', 'numeric', ...$numberRules],
        ]);

        try {
            $installation = $this->active->resolve();
            $this->active->configureClinicDatabase($installation);
            $participant = $this->participants->lookup($installation, $data['jenis'], $data['nomor']);
            $patient = $participant['noka'] !== '' ? DB::connection('clinic')->table('pasien')->where('no_peserta', $participant['noka'])->first() : null;
            if ($patient && $participant['aktif']) $request->session()->put('kiosk_verified_rm', $patient->no_rkm_medis);

            return response()->json([
                'status' => true,
                'participant' => $participant,
                'participant_token' => Crypt::encryptString(json_encode(['participant' => $participant, 'issued_at' => now()->timestamp, 'clinic_id' => $installation->clinic_id], JSON_UNESCAPED_UNICODE)),
                'patient' => $patient ? $this->patient($patient) : null,
                'schedules' => $participant['aktif'] ? $this->schedules() : [],
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function createPatient(Request $request): JsonResponse
    {
        $data = $request->validate([
            'participant_token' => ['required', 'string'],
            'province_name' => ['required', 'string', 'max:30'],
            'regency_name' => ['required', 'string', 'max:60'],
            'district_name' => ['required', 'string', 'max:60'],
            'village_name' => ['required', 'string', 'max:60'],
            'alamat_detail' => ['required', 'string', 'max:180'],
        ]);

        try {
            $tokenData = json_decode(Crypt::decryptString($data['participant_token']), true, 512, JSON_THROW_ON_ERROR);
            if ((int) ($tokenData['issued_at'] ?? 0) < now()->subMinutes(15)->timestamp) throw new RuntimeException('Sesi verifikasi peserta telah berakhir. Silakan cek kembali nomor peserta.');
            $participant = $tokenData['participant'] ?? [];
            if (empty($participant['aktif'])) throw new RuntimeException('Peserta tidak aktif dan tidak dapat didaftarkan melalui Anjungan.');
            foreach (['nik', 'noka', 'nama', 'tanggal_lahir'] as $field) if (empty($participant[$field])) throw new RuntimeException("Data {$field} dari BPJS belum lengkap.");

            $installation = $this->active->resolve();
            if ((int) ($tokenData['clinic_id'] ?? 0) !== (int) $installation->clinic_id) throw new RuntimeException('Klinik pada sesi verifikasi tidak sesuai.');
            $this->active->configureClinicDatabase($installation);
            $patient = DB::connection('clinic')->transaction(function () use ($participant, $data) {
                $existing = DB::connection('clinic')->table('pasien')->where('no_peserta', $participant['noka'])->lockForUpdate()->first();
                if ($existing) return $existing;

                $region = [
                    'kd_prop' => $this->masterId('propinsi', 'kd_prop', 'nm_prop', $data['province_name']),
                    'kd_kab' => $this->masterId('kabupaten', 'kd_kab', 'nm_kab', $data['regency_name']),
                    'kd_kec' => $this->masterId('kecamatan', 'kd_kec', 'nm_kec', $data['district_name']),
                    'kd_kel' => $this->masterId('kelurahan', 'kd_kel', 'nm_kel', $data['village_name']),
                ];
                $rm = $this->nextMedicalRecord();
                $birth = CarbonImmutable::parse($participant['tanggal_lahir']);
                $age = $birth->diff(now('Asia/Jakarta'));

                DB::connection('clinic')->table('pasien')->insert([
                    'no_rkm_medis' => $rm, 'nm_pasien' => mb_strtoupper($participant['nama']), 'no_ktp' => $participant['nik'],
                    'jk' => $participant['jenis_kelamin'], 'tmp_lahir' => '-', 'tgl_lahir' => $birth->toDateString(), 'nm_ibu' => '-',
                    'alamat' => $data['alamat_detail'], 'gol_darah' => '-', 'pekerjaan' => '-', 'stts_nikah' => 'BELUM MENIKAH',
                    'agama' => '-', 'tgl_daftar' => now('Asia/Jakarta')->toDateString(), 'no_tlp' => $participant['no_hp'] ?: '-',
                    'umur' => "{$age->y} Th {$age->m} Bl {$age->d} Hr", 'pnd' => '-', 'keluarga' => 'DIRI SENDIRI',
                    'namakeluarga' => mb_strtoupper($participant['nama']), 'kd_pj' => 'BPJ', 'no_peserta' => $participant['noka'],
                    ...$region, 'pekerjaanpj' => '-', 'alamatpj' => $data['alamat_detail'], 'kelurahanpj' => $data['village_name'],
                    'kecamatanpj' => $data['district_name'], 'kabupatenpj' => $data['regency_name'], 'perusahaan_pasien' => '-',
                    'suku_bangsa' => 1, 'bahasa_pasien' => 1, 'cacat_fisik' => 1, 'email' => '-', 'nip' => '-',
                    'propinsipj' => $data['province_name'],
                ]);
                return DB::connection('clinic')->table('pasien')->where('no_rkm_medis', $rm)->first();
            });

            $request->session()->put('kiosk_verified_rm', $patient->no_rkm_medis);
            return response()->json(['status' => true, 'message' => 'Nomor rekam medis berhasil dibuat.', 'patient' => $this->patient($patient), 'schedules' => $this->schedules()]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate(['no_rkm_medis' => ['required', 'string', 'max:15'], 'kd_poli' => ['required', 'string', 'max:5'], 'kd_dokter' => ['required', 'string', 'max:20']]);

        try {
            if ($request->session()->get('kiosk_verified_rm') !== $data['no_rkm_medis']) throw new RuntimeException('Pasien belum diverifikasi pada sesi Anjungan ini.');
            $installation = $this->active->resolve();
            $this->active->configureClinicDatabase($installation);
            $result = DB::connection('clinic')->transaction(function () use ($data) {
                $patient = DB::connection('clinic')->table('pasien')->where('no_rkm_medis', $data['no_rkm_medis'])->lockForUpdate()->first();
                if (!$patient) throw new RuntimeException('Data pasien tidak ditemukan.');
                $schedule = $this->scheduleQuery()->where('j.kd_poli', $data['kd_poli'])->where('j.kd_dokter', $data['kd_dokter'])->first();
                if (!$schedule) throw new RuntimeException('Jadwal dokter tidak tersedia hari ini.');
                $today = now('Asia/Jakarta')->toDateString();
                $existing = DB::connection('clinic')->table('reg_periksa')->where('no_rkm_medis', $patient->no_rkm_medis)->where('kd_poli', $data['kd_poli'])->whereDate('tgl_registrasi', $today)->first();
                if ($existing) return ['existing' => true, 'no_rawat' => $existing->no_rawat, 'no_antrean' => $existing->no_reg];

                DB::connection('clinic')->table('reg_periksa')->lockForUpdate()->count();
                $lastRawat = DB::connection('clinic')->table('reg_periksa')->whereDate('tgl_registrasi', $today)->orderByDesc('no_rawat')->value('no_rawat');
                $sequence = $lastRawat ? ((int) substr($lastRawat, -6) + 1) : 1;
                $noRawat = str_replace('-', '/', $today).'/'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
                $prefix = $this->doctorPrefix($schedule->nm_dokter);
                $lastQueue = DB::connection('clinic')->table('reg_periksa')->whereDate('tgl_registrasi', $today)->where('kd_dokter', $data['kd_dokter'])->orderByDesc('no_reg')->value('no_reg');
                $queueSequence = $lastQueue ? ((int) substr($lastQueue, -3) + 1) : 1;
                $noQueue = $prefix.'-'.str_pad((string) $queueSequence, 3, '0', STR_PAD_LEFT);
                $oldPatient = DB::connection('clinic')->table('reg_periksa')->where('no_rkm_medis', $patient->no_rkm_medis)->exists();
                $oldPoli = DB::connection('clinic')->table('reg_periksa')->where('no_rkm_medis', $patient->no_rkm_medis)->where('kd_poli', $data['kd_poli'])->exists();
                $age = CarbonImmutable::parse($patient->tgl_lahir)->diffInYears(now('Asia/Jakarta'));
                $responsibleAddress = $this->responsibleAddress($patient);

                DB::connection('clinic')->table('reg_periksa')->insert([
                    'no_reg' => $noQueue, 'no_rawat' => $noRawat, 'tgl_registrasi' => $today, 'jam_reg' => now('Asia/Jakarta')->format('H:i:s'),
                    'kd_dokter' => $data['kd_dokter'], 'no_rkm_medis' => $patient->no_rkm_medis, 'kd_poli' => $data['kd_poli'],
                    'p_jawab' => $patient->nm_pasien, 'almt_pj' => $responsibleAddress, 'hubunganpj' => 'DIRI SENDIRI',
                    'biaya_reg' => $oldPoli ? $schedule->registrasilama : $schedule->registrasi, 'stts' => 'Belum',
                    'stts_daftar' => $oldPatient ? 'Lama' : 'Baru', 'status_lanjut' => 'Ralan', 'kd_pj' => 'BPJ',
                    'umurdaftar' => $age, 'sttsumur' => 'Th', 'status_bayar' => 'Belum Bayar', 'status_poli' => $oldPoli ? 'Lama' : 'Baru',
                ]);
                return ['existing' => false, 'no_rawat' => $noRawat, 'no_antrean' => $noQueue];
            });

            $request->session()->forget('kiosk_verified_rm');
            return response()->json(['status' => true, 'message' => $result['existing'] ? 'Pasien sudah terdaftar di poli hari ini.' : 'Antrean berhasil diambil.', ...$result]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function schedules(): array
    {
        return $this->scheduleQuery()->get()->groupBy('kd_poli')->map(fn ($rows) => ['kd_poli' => $rows->first()->kd_poli, 'nm_poli' => $rows->first()->nm_poli, 'doctors' => $rows->map(fn ($r) => ['kd_dokter' => $r->kd_dokter, 'nm_dokter' => $r->nm_dokter, 'jam_mulai' => substr($r->jam_mulai, 0, 5), 'jam_selesai' => substr($r->jam_selesai, 0, 5), 'kuota' => $r->kuota])->values()])->values()->all();
    }

    private function scheduleQuery()
    {
        $day = ['Monday'=>'SENIN','Tuesday'=>'SELASA','Wednesday'=>'RABU','Thursday'=>'KAMIS','Friday'=>'JUMAT','Saturday'=>'SABTU','Sunday'=>'AKHAD'][now('Asia/Jakarta')->englishDayOfWeek];
        return DB::connection('clinic')->table('jadwal as j')->join('poliklinik as p', 'p.kd_poli', '=', 'j.kd_poli')->join('dokter as d', 'd.kd_dokter', '=', 'j.kd_dokter')->where('j.hari_kerja', $day)->where('p.status', '1')->where('d.status', '1')->select(['j.kd_poli','p.nm_poli','p.registrasi','p.registrasilama','j.kd_dokter','d.nm_dokter','j.jam_mulai','j.jam_selesai','j.kuota'])->orderBy('p.nm_poli')->orderBy('j.jam_mulai');
    }

    private function patient(object $patient): array
    {
        return ['no_rkm_medis' => $patient->no_rkm_medis, 'nama' => $patient->nm_pasien, 'nik' => $patient->no_ktp, 'noka' => $patient->no_peserta, 'jenis_kelamin' => $patient->jk, 'tanggal_lahir' => $patient->tgl_lahir, 'no_hp' => $patient->no_tlp, 'alamat' => $patient->alamat];
    }

    private function masterId(string $table, string $id, string $name, string $value): int
    {
        $normalized = mb_strtoupper(trim($value));
        $existing = DB::connection('clinic')->table($table)->whereRaw("UPPER({$name}) = ?", [$normalized])->value($id);
        return $existing ? (int) $existing : (int) DB::connection('clinic')->table($table)->insertGetId([$name => $normalized]);
    }

    private function nextMedicalRecord(): string
    {
        $last = DB::connection('clinic')->table('pasien')->whereRaw("no_rkm_medis REGEXP '^[0-9]{2}-[0-9]{2}-[0-9]{2}$'")->orderByRaw("CAST(REPLACE(no_rkm_medis, '-', '') AS UNSIGNED) DESC")->lockForUpdate()->value('no_rkm_medis');
        $next = ((int) str_replace('-', '', (string) $last)) + 1;
        $digits = str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        return substr($digits, 0, 2).'-'.substr($digits, 2, 2).'-'.substr($digits, 4, 2);
    }

    private function doctorPrefix(string $name): string
    {
        $name = preg_replace('/^\s*dr\.?\s*/i', '', $name);
        $letters = preg_replace('/[^A-Za-z]/', '', $name);
        if ($letters === '') return 'AN';
        $first = mb_strtoupper($letters[0]);
        $rest = substr($letters, 1);
        preg_match('/[bcdfghjklmnpqrstvwxyz]/i', $rest, $match);
        return $first.mb_strtoupper($match[0] ?? ($rest[0] ?? 'N'));
    }

    private function responsibleAddress(object $patient): string
    {
        $parts = [
            $this->addressPart($patient->kelurahanpj ?? ''),
            $this->addressPart($patient->kecamatanpj ?? ''),
            $this->addressPart($patient->kabupatenpj ?? ''),
            $this->addressPart($patient->propinsipj ?? ''),
        ];

        $address = implode(', ', array_filter($parts, fn ($value) => $value !== ''));
        return mb_strimwidth($address !== '' ? $address : '-', 0, 200, '');
    }

    private function addressPart(mixed $value): string
    {
        $value = trim((string) $value);
        return $value === '' || $value === '-' ? '' : $value;
    }
}
