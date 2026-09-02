<?php

namespace App\Http\Controllers;

use App\Services\ActiveInstallation;
use App\Services\BpjsParticipantService;
use App\Services\PcareOnlineRegistrationService;
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
            'mode' => ['required', Rule::in(['scheduled', 'online'])],
            'jenis' => ['required', Rule::in(['nik', 'noka'])],
            'nomor' => ['required', 'numeric', ...$numberRules],
        ]);

        try {
            $request->session()->forget(['kiosk_verified_rm', 'kiosk_verified_participant', 'kiosk_service_mode']);
            $installation = $this->active->resolve();
            $this->active->configureClinicDatabase($installation);
            $participant = $this->participants->lookup($installation, $data['jenis'], $data['nomor']);
            $request->session()->put('kiosk_service_mode', $data['mode']);
            $patient = $this->findPatientByParticipant($participant);
            if ($patient) $patient = $this->fillMissingParticipantIdentifiers($patient, $participant);
            if ($patient && $participant['aktif']) {
                $request->session()->put('kiosk_verified_rm', $patient->no_rkm_medis);
                $request->session()->put('kiosk_verified_participant', $participant);
            }

            return response()->json([
                'status' => true,
                'participant' => $participant,
                'participant_token' => Crypt::encryptString(json_encode(['participant' => $participant, 'issued_at' => now()->timestamp, 'clinic_id' => $installation->clinic_id], JSON_UNESCAPED_UNICODE)),
                'patient' => $patient ? $this->patient($patient) : null,
                'suggested_rm' => !$patient && $participant['aktif'] ? $this->nextMedicalRecord() : null,
                'selection' => $participant['aktif'] ? $this->selectionOptions($data['mode']) : $this->emptySelection($data['mode']),
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
            'no_rkm_medis' => ['required', 'string', 'regex:/^[0-9]{2}-[0-9]{2}-[0-9]{2}$/'],
            'province_name' => ['required', 'string', 'max:30'],
            'regency_name' => ['required', 'string', 'max:60'],
            'district_name' => ['required', 'string', 'max:60'],
            'village_name' => ['required', 'string', 'max:60'],
            'alamat_detail' => ['required', 'string', 'max:180'],
        ], [
            'no_rkm_medis.required' => 'Nomor rekam medis wajib diisi.',
            'no_rkm_medis.regex' => 'Format nomor rekam medis harus 00-00-00.',
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
                $existing = $this->findPatientByParticipant($participant, true);
                if ($existing) return $this->fillMissingParticipantIdentifiers($existing, $participant);

                $rm = $data['no_rkm_medis'];
                $rmExists = DB::connection('clinic')->table('pasien')->where('no_rkm_medis', $rm)->lockForUpdate()->exists();
                if ($rmExists) throw new RuntimeException("Nomor rekam medis {$rm} sudah digunakan. Silakan gunakan nomor lain.");

                $region = [
                    'kd_prop' => $this->masterId('propinsi', 'kd_prop', 'nm_prop', $data['province_name']),
                    'kd_kab' => $this->masterId('kabupaten', 'kd_kab', 'nm_kab', $data['regency_name']),
                    'kd_kec' => $this->masterId('kecamatan', 'kd_kec', 'nm_kec', $data['district_name']),
                    'kd_kel' => $this->masterId('kelurahan', 'kd_kel', 'nm_kel', $data['village_name']),
                ];
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
            $request->session()->put('kiosk_verified_participant', $participant);
            $mode = (string) $request->session()->get('kiosk_service_mode', 'scheduled');
            return response()->json(['status' => true, 'message' => 'Nomor rekam medis berhasil dibuat.', 'patient' => $this->patient($patient), 'selection' => $this->selectionOptions($mode)]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function register(Request $request, PcareOnlineRegistrationService $onlineRegistrations): JsonResponse
    {
        $mode = (string) $request->session()->get('kiosk_service_mode');
        $rules = ['no_rkm_medis' => ['required', 'string', 'max:15']];
        if (in_array($mode, ['scheduled', 'online'], true)) {
            $rules['kd_poli'] = ['required', 'string', 'max:5'];
            $rules['kd_dokter'] = ['required', 'string', 'max:20'];
        }
        $data = $request->validate($rules);
        $onlineDebug = null;

        try {
            if ($request->session()->get('kiosk_verified_rm') !== $data['no_rkm_medis']) throw new RuntimeException('Pasien belum diverifikasi pada sesi Anjungan ini.');
            $participant = $request->session()->get('kiosk_verified_participant');
            if (!is_array($participant)) throw new RuntimeException('Data kepesertaan belum diverifikasi pada sesi Anjungan ini.');
            if (!in_array($mode, ['scheduled', 'online'], true)) throw new RuntimeException('Jenis layanan Anjungan belum dipilih.');
            $installation = $this->active->resolve();
            $this->active->configureClinicDatabase($installation);

            if ($mode === 'online') {
                // Kirim ke PCare terlebih dahulu agar request dan respons aktual
                // selalu tersedia pada panel debug. Registrasi lokal hanya dibuat
                // sesudah PCare menyatakan pendaftaran berhasil.
                $service = $this->selectedOnlineService($data['kd_poli'], $data['kd_dokter']);
                $result = $onlineRegistrations->register(
                    $installation,
                    (string) ($participant['noka'] ?? ''),
                    (string) ($participant['fktp_kode'] ?? ''),
                    (string) $service->kd_poli_pcare,
                    $onlineDebug
                );
                $localRegistration = $this->saveLocalRegistration(
                    $data['no_rkm_medis'],
                    $data['kd_poli'],
                    $data['kd_dokter'],
                    $service,
                    $participant,
                    'Sudah'
                );
                $request->session()->forget(['kiosk_verified_rm', 'kiosk_verified_participant', 'kiosk_service_mode']);
                return response()->json([
                    'status' => true,
                    'message' => (string) data_get($result, 'metadata.message', 'Pendaftaran kunjungan online berhasil.'),
                    'registration' => $result['response'],
                    'payload' => $result['payload'],
                    ...$localRegistration,
                    'debug' => $onlineDebug,
                ]);
            }

            $service = $this->selectedService($data['kd_poli'], $data['kd_dokter']);
            $result = $this->saveLocalRegistration($data['no_rkm_medis'], $data['kd_poli'], $data['kd_dokter'], $service, $participant);

            $request->session()->forget(['kiosk_verified_rm', 'kiosk_verified_participant', 'kiosk_service_mode']);
            return response()->json(['status' => true, 'message' => $result['existing'] ? 'Pasien sudah terdaftar di poli hari ini.' : 'Antrean berhasil diambil.', ...$result]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'debug' => $mode === 'online' ? $onlineDebug : null], 422);
        }
    }

    private function schedules(): array
    {
        return $this->scheduleQuery()->get()->groupBy('kd_poli')->map(fn ($rows) => ['kd_poli' => $rows->first()->kd_poli, 'nm_poli' => $rows->first()->nm_poli, 'doctors' => $rows->map(fn ($r) => ['kd_dokter' => $r->kd_dokter, 'nm_dokter' => $r->nm_dokter, 'jam_mulai' => substr($r->jam_mulai, 0, 5), 'jam_selesai' => substr($r->jam_selesai, 0, 5), 'kuota' => $r->kuota])->values()])->values()->all();
    }

    private function selectionOptions(string $mode): array
    {
        if ($mode === 'online') {
            return ['mode' => 'online', 'polis' => $this->onlineSchedules(), 'doctors' => []];
        }

        return ['mode' => 'scheduled', 'polis' => $this->schedules(), 'doctors' => []];
    }

    private function emptySelection(string $mode): array
    {
        return ['mode' => $mode, 'polis' => [], 'doctors' => []];
    }

    private function selectedService(string $poliCode, string $doctorCode): object
    {
        $schedule = $this->scheduleQuery()->where('j.kd_poli', $poliCode)->where('j.kd_dokter', $doctorCode)->first();
        if (!$schedule) throw new RuntimeException('Jadwal dokter tidak tersedia hari ini.');
        return $schedule;
    }

    private function selectedOnlineService(string $poliCode, string $doctorCode): object
    {
        $poli = DB::connection('clinic')->table('poliklinik as p')
            ->join('maping_poliklinik_pcare as mpp', 'mpp.kd_poli_rs', '=', 'p.kd_poli')
            ->where('p.status', '1')
            ->where('p.kd_poli', $poliCode)
            ->whereNotNull('mpp.kd_poli_pcare')
            ->where('mpp.kd_poli_pcare', '<>', '')
            ->select(['p.kd_poli', 'p.nm_poli', 'p.registrasi', 'p.registrasilama', 'mpp.kd_poli_pcare'])
            ->first();
        if (!$poli) throw new RuntimeException('Poli aktif belum memiliki mapping kode poli PCare.');

        $doctor = DB::connection('clinic')->table('dokter')
            ->where('status', '1')
            ->where('kd_dokter', $doctorCode)
            ->select(['kd_dokter', 'nm_dokter'])
            ->first();
        if (!$doctor) throw new RuntimeException('Dokter yang dipilih tidak aktif atau tidak ditemukan.');

        return (object) [...(array) $poli, ...(array) $doctor];
    }

    private function onlineSchedules(): array
    {
        $doctors = DB::connection('clinic')->table('dokter')
            ->where('status', '1')
            ->orderBy('nm_dokter')
            ->get(['kd_dokter', 'nm_dokter'])
            ->map(fn ($doctor) => [
                'kd_dokter' => $doctor->kd_dokter,
                'nm_dokter' => $doctor->nm_dokter,
            ])->values()->all();

        return DB::connection('clinic')->table('poliklinik as p')
            ->join('maping_poliklinik_pcare as mpp', 'mpp.kd_poli_rs', '=', 'p.kd_poli')
            ->where('p.status', '1')
            ->whereNotNull('mpp.kd_poli_pcare')
            ->where('mpp.kd_poli_pcare', '<>', '')
            ->orderBy('p.nm_poli')
            ->get(['p.kd_poli', 'p.nm_poli', 'mpp.kd_poli_pcare'])
            ->map(fn ($poli) => [
                'kd_poli' => $poli->kd_poli,
                'nm_poli' => $poli->nm_poli,
                'kd_poli_pcare' => $poli->kd_poli_pcare,
                'doctors' => $doctors,
            ])->values()->all();
    }

    private function saveLocalRegistration(string $medicalRecord, string $poliCode, string $doctorCode, object $service, array $participant, string $status = 'Belum'): array
    {
        return DB::connection('clinic')->transaction(function () use ($medicalRecord, $poliCode, $doctorCode, $service, $participant, $status) {
            $patient = DB::connection('clinic')->table('pasien')->where('no_rkm_medis', $medicalRecord)->lockForUpdate()->first();
            if (!$patient) throw new RuntimeException('Data pasien tidak ditemukan.');

            $today = now('Asia/Jakarta')->toDateString();
            $existing = DB::connection('clinic')->table('reg_periksa')->where('no_rkm_medis', $patient->no_rkm_medis)->where('kd_poli', $poliCode)->whereDate('tgl_registrasi', $today)->first();
            if ($existing) {
                if ($status === 'Sudah' && $existing->stts !== 'Sudah') {
                    DB::connection('clinic')->table('reg_periksa')->where('no_rawat', $existing->no_rawat)->update(['stts' => 'Sudah']);
                }
                $this->saveRegistrationPrograms($existing->no_rawat, $participant);
                return ['existing' => true, 'no_rawat' => $existing->no_rawat, 'no_antrean' => $existing->no_reg];
            }

            DB::connection('clinic')->table('reg_periksa')->lockForUpdate()->count();
            $lastRawat = DB::connection('clinic')->table('reg_periksa')->whereDate('tgl_registrasi', $today)->orderByDesc('no_rawat')->value('no_rawat');
            $sequence = $lastRawat ? ((int) substr($lastRawat, -6) + 1) : 1;
            $noRawat = str_replace('-', '/', $today).'/'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $prefix = $this->doctorPrefix($service->nm_dokter);
            $lastQueue = DB::connection('clinic')->table('reg_periksa')->whereDate('tgl_registrasi', $today)->where('kd_dokter', $doctorCode)->orderByDesc('no_reg')->value('no_reg');
            $queueSequence = $lastQueue ? ((int) substr($lastQueue, -3) + 1) : 1;
            $noQueue = $prefix.'-'.str_pad((string) $queueSequence, 3, '0', STR_PAD_LEFT);
            $oldPatient = DB::connection('clinic')->table('reg_periksa')->where('no_rkm_medis', $patient->no_rkm_medis)->exists();
            $oldPoli = DB::connection('clinic')->table('reg_periksa')->where('no_rkm_medis', $patient->no_rkm_medis)->where('kd_poli', $poliCode)->exists();
            $age = CarbonImmutable::parse($patient->tgl_lahir)->diffInYears(now('Asia/Jakarta'));

            DB::connection('clinic')->table('reg_periksa')->insert([
                'no_reg' => $noQueue, 'no_rawat' => $noRawat, 'tgl_registrasi' => $today, 'jam_reg' => now('Asia/Jakarta')->format('H:i:s'),
                'kd_dokter' => $doctorCode, 'no_rkm_medis' => $patient->no_rkm_medis, 'kd_poli' => $poliCode,
                'p_jawab' => $patient->nm_pasien, 'almt_pj' => $this->responsibleAddress($patient), 'hubunganpj' => 'DIRI SENDIRI',
                'biaya_reg' => $oldPoli ? $service->registrasilama : $service->registrasi, 'stts' => $status,
                'stts_daftar' => $oldPatient ? 'Lama' : 'Baru', 'status_lanjut' => 'Ralan', 'kd_pj' => 'BPJ',
                'umurdaftar' => $age, 'sttsumur' => 'Th', 'status_bayar' => 'Belum Bayar', 'status_poli' => $oldPoli ? 'Lama' : 'Baru',
            ]);
            $this->saveRegistrationPrograms($noRawat, $participant);

            return ['existing' => false, 'no_rawat' => $noRawat, 'no_antrean' => $noQueue];
        });
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

    private function saveRegistrationPrograms(string $careNumber, array $participant): void
    {
        DB::connection('clinic')->table('registrasi_prolanis')->updateOrInsert(
            ['no_rawat' => $careNumber],
            [
                'peserta_prolanis' => $participant['peserta_prolanis'] ?? null,
                'peserta_prb' => $participant['peserta_prb'] ?? null,
            ]
        );
    }

    private function findPatientByParticipant(array $participant, bool $lockForUpdate = false): ?object
    {
        $card = trim((string) ($participant['noka'] ?? ''));
        $nik = trim((string) ($participant['nik'] ?? ''));
        if ($card === '' && $nik === '') return null;

        $query = DB::connection('clinic')->table('pasien')->where(function ($query) use ($card, $nik) {
            if ($card !== '') $query->where('no_peserta', $card);
            if ($nik !== '') {
                $card !== ''
                    ? $query->orWhere('no_ktp', $nik)
                    : $query->where('no_ktp', $nik);
            }
        });

        if ($card !== '') $query->orderByRaw('CASE WHEN no_peserta = ? THEN 0 ELSE 1 END', [$card]);
        if ($lockForUpdate) $query->lockForUpdate();

        return $query->first();
    }

    private function fillMissingParticipantIdentifiers(object $patient, array $participant): object
    {
        $identifiers = [
            'no_peserta' => trim((string) ($participant['noka'] ?? '')),
            'no_ktp' => trim((string) ($participant['nik'] ?? '')),
        ];

        foreach ($identifiers as $column => $value) {
            $currentValue = trim((string) ($patient->{$column} ?? ''));
            if ($value === '' || !in_array($currentValue, ['', '-', '0'], true)) continue;

            $updated = DB::connection('clinic')->table('pasien')
                ->where('no_rkm_medis', $patient->no_rkm_medis)
                ->where(function ($query) use ($column) {
                    $query->whereNull($column)->orWhereIn($column, ['', '-', '0']);
                })
                ->update([$column => $value]);

            if ($updated) $patient->{$column} = $value;
        }

        return $patient;
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
