<?php

namespace App\Services;

use App\Models\BaseUrlConfiguration;
use App\Models\InstallationSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;
use RuntimeException;
use Throwable;

class PcareDiagnosisService
{
    public function syncNonSpecialist(InstallationSetting $installation, string $careNumber): array
    {
        $diagnoses = DB::connection('clinic')->table('diagnosa_pasien')
            ->where('no_rawat', $careNumber)
            ->orderBy('prioritas')
            ->get(['no_rawat', 'kd_penyakit', 'status', 'prioritas', 'status_penyakit', 'nonSpesialis']);

        if ($diagnoses->isEmpty()) throw new RuntimeException('Data diagnosa pasien tidak ditemukan.');

        $results = [];
        $succeeded = 0;
        $failed = 0;

        foreach ($diagnoses as $diagnosis) {
            try {
                $results[] = $this->syncDiagnosis($installation, $diagnosis);
                $succeeded++;
            } catch (Throwable $e) {
                report($e);
                $failed++;
                $results[] = [
                    'kd_penyakit' => trim((string) $diagnosis->kd_penyakit),
                    'status_diagnosa' => (string) $diagnosis->status,
                    'status' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'status' => $succeeded > 0,
            'message' => 'Proses sinkronisasi nonSpesialis selesai.',
            'no_rawat' => $careNumber,
            'total_diagnosa' => $diagnoses->count(),
            'jumlah_berhasil' => $succeeded,
            'jumlah_gagal' => $failed,
            'hasil_update' => $results,
        ];
    }

    private function syncDiagnosis(InstallationSetting $installation, object $diagnosis): array
    {
        $code = trim((string) $diagnosis->kd_penyakit);
        if ($code === '') throw new RuntimeException('Kode diagnosa kosong.');

        $pcare = $this->lookup($installation, $code);
        $oldValue = $diagnosis->nonSpesialis;
        $newValue = $pcare['non_spesialis'] ? 'true' : 'false';
        $action = $oldValue === null || trim((string) $oldValue) === ''
            ? 'insert_value'
            : ($this->booleanValue($oldValue) !== $pcare['non_spesialis'] ? 'update_changed' : 'no_change');

        DB::connection('clinic')->table('diagnosa_pasien')
            ->where('no_rawat', $diagnosis->no_rawat)
            ->where('kd_penyakit', $diagnosis->kd_penyakit)
            ->where('status', $diagnosis->status)
            ->update(['nonSpesialis' => $newValue]);

        return [
            'kd_penyakit' => $code,
            'nmDiag' => $pcare['nama'],
            'status_diagnosa' => (string) $diagnosis->status,
            'status' => true,
            'aksi' => $action,
            'message' => 'Berhasil menyinkronkan nonSpesialis.',
            'nonSpesialis_lama' => $oldValue,
            'nonSpesialis_baru' => $newValue,
        ];
    }

    private function lookup(InstallationSetting $installation, string $diagnosisCode): array
    {
        $bpjs = $installation->clinic->bpjsConfiguration;
        if (!$bpjs) throw new RuntimeException('Konfigurasi BPJS klinik belum tersedia.');

        $timestamp = (string) time();
        $signature = base64_encode(hash_hmac('sha256', $bpjs->cons_id.'&'.$timestamp, $bpjs->secret_key, true));
        $url = $this->pcareBaseUrl(BaseUrlConfiguration::firstOrFail()->base_url_pcare)
            .'diagnosa/'.rawurlencode($diagnosisCode).'/0/500';

        try {
            $response = Http::acceptJson()->timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
                'X-cons-id' => $bpjs->cons_id,
                'X-timestamp' => $timestamp,
                'X-signature' => $signature,
                'X-authorization' => 'Basic '.base64_encode($bpjs->username.':'.$bpjs->password.':'.$bpjs->kode_aplikasi),
                'user_key' => $bpjs->user_key_pcare,
            ])->get($url);
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal menghubungi layanan diagnosa PCare.', 0, $e);
        }

        if (!$response->successful()) throw new RuntimeException('Layanan PCare mengembalikan HTTP '.$response->status().'.');
        $json = $response->json();
        if (!is_array($json)) throw new RuntimeException('Respons diagnosa PCare tidak valid.');

        $metadata = $json['metaData'] ?? $json['metadata'] ?? null;
        if (!is_array($metadata)) throw new RuntimeException('Metadata respons diagnosa PCare tidak tersedia.');
        if ((int) ($metadata['code'] ?? 0) !== 200) throw new RuntimeException((string) ($metadata['message'] ?? 'Permintaan diagnosa PCare gagal.'));

        $decrypted = $this->decrypt($bpjs->cons_id.$bpjs->secret_key.$timestamp, (string) ($json['response'] ?? ''));
        if ($decrypted === false || $decrypted === '') throw new RuntimeException('Data diagnosa PCare tidak dapat didekripsi.');
        $decompressed = LZString::decompressFromEncodedURIComponent($decrypted);
        if (!is_string($decompressed) || $decompressed === '') throw new RuntimeException('Data diagnosa PCare tidak dapat didekompresi.');
        $payload = json_decode($decompressed, true);
        if (!is_array($payload)) throw new RuntimeException('Format data diagnosa PCare tidak dikenali.');

        foreach (($payload['list'] ?? []) as $item) {
            if (strcasecmp(trim((string) ($item['kdDiag'] ?? '')), $diagnosisCode) !== 0) continue;
            if (!array_key_exists('nonSpesialis', $item)) throw new RuntimeException('Nilai nonSpesialis tidak tersedia pada diagnosa PCare.');
            return [
                'nama' => trim((string) ($item['nmDiag'] ?? '')),
                'non_spesialis' => $this->booleanValue($item['nonSpesialis']),
            ];
        }

        throw new RuntimeException("Diagnosa {$diagnosisCode} tidak ditemukan pada respons PCare.");
    }

    private function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        return in_array(mb_strtolower(trim((string) $value)), ['1', 'true', 'ya', 'yes'], true);
    }

    private function decrypt(string $key, string $payload): string|false
    {
        $keyHash = hex2bin(hash('sha256', $key));
        return openssl_decrypt(base64_decode($payload), 'AES-256-CBC', $keyHash, OPENSSL_RAW_DATA, substr((string) $keyHash, 0, 16));
    }

    private function pcareBaseUrl(string $configured): string
    {
        $url = rtrim(trim($configured), '/');
        if (preg_match('~^(https?://[^/]+)(?:/.*)?$~i', $url, $matches)) {
            if (!preg_match('~/pcare-rest(?:-v\d+(?:\.\d+)?)?$~i', $url)) return $matches[1].'/pcare-rest/';
        }
        return $url.'/';
    }
}
