<?php

namespace App\Services;

use App\Models\InstallationSetting;
use App\Models\ServiceLog;
use App\Models\ServiceRun;
use Illuminate\Support\Facades\DB;
use Throwable;

class RegistrasiProlanisSyncService
{
    public function __construct(private readonly BpjsParticipantService $participants)
    {
    }

    public function scan(InstallationSetting $installation, ServiceRun $run, callable $output): void
    {
        $date = now('Asia/Jakarta')->toDateString();
        $rows = DB::connection('clinic')->select($this->query(), [$date]);

        if (!$rows) {
            $output('info', 'Tidak ada registrasi BPJS baru yang perlu disinkronkan.');
            return;
        }

        $this->log($installation, $run, $output, 'info', 'scan', count($rows)." registrasi BPJS perlu disinkronkan untuk {$date}.");

        foreach ($rows as $row) {
            $data = (array) $row;
            $run->increment('processed');

            try {
                $this->sync($installation, $run, $data, $output);
            } catch (Throwable $e) {
                $run->increment('failed');
                $this->log($installation, $run, $output, 'error', 'failed', "{$data['no_rawat']}: {$e->getMessage()}", $data['no_rawat']);
            }

            usleep(250000);
        }
    }

    private function sync(InstallationSetting $installation, ServiceRun $run, array $data, callable $output): void
    {
        [$type, $number] = $this->participantIdentity($data);
        $started = microtime(true);
        $participant = $this->participants->lookup($installation, $type, $number);

        DB::connection('clinic')->table('registrasi_prolanis')->updateOrInsert(
            ['no_rawat' => $data['no_rawat']],
            [
                'peserta_prolanis' => $participant['peserta_prolanis'] ?? null,
                'peserta_prb' => $participant['peserta_prb'] ?? null,
            ]
        );

        $run->increment('succeeded');
        $prolanis = $participant['peserta_prolanis'] ?? '-';
        $prb = $participant['peserta_prb'] ?? '-';
        $this->log(
            $installation,
            $run,
            $output,
            'success',
            'success',
            "{$data['no_rawat']} ({$data['nm_pasien']}) => Prolanis: {$prolanis} | PRB: {$prb}",
            $data['no_rawat'],
            (int) ((microtime(true) - $started) * 1000),
            ['identity_type' => $type]
        );
    }

    private function participantIdentity(array $data): array
    {
        $card = preg_replace('/\D/', '', (string) ($data['no_peserta'] ?? ''));
        if (strlen($card) === 13) return ['noka', $card];

        $nik = preg_replace('/\D/', '', (string) ($data['no_ktp'] ?? ''));
        if (strlen($nik) === 16) return ['nik', $nik];

        throw new \RuntimeException('Nomor kartu BPJS dan NIK tidak valid atau belum tersedia.');
    }

    private function log(
        InstallationSetting $installation,
        ServiceRun $run,
        callable $output,
        string $level,
        string $status,
        string $message,
        ?string $reference = null,
        ?int $duration = null,
        array $context = []
    ): void {
        ServiceLog::create([
            'service_run_id' => $run->id,
            'clinic_id' => $installation->clinic_id,
            'service_name' => 'registrasi_prolanis_sync',
            'level' => $level,
            'status' => $status,
            'reference' => $reference,
            'message' => $message,
            'duration_ms' => $duration,
            'context' => $context ?: null,
        ]);
        $output($level, $message);
    }

    private function query(): string
    {
        return <<<'SQL'
SELECT rp.no_rawat, ps.nm_pasien, ps.no_peserta, ps.no_ktp
FROM reg_periksa rp
INNER JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
LEFT JOIN registrasi_prolanis rpro ON rpro.no_rawat = rp.no_rawat
WHERE rp.tgl_registrasi = ?
  AND rpro.no_rawat IS NULL
  AND (
      TRIM(COALESCE(ps.no_peserta, '')) REGEXP '^[0-9]{13}$'
      OR TRIM(COALESCE(ps.no_ktp, '')) REGEXP '^[0-9]{16}$'
  )
ORDER BY rp.jam_reg, rp.no_rawat
SQL;
    }
}
