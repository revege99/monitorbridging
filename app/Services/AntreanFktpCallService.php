<?php

namespace App\Services;

use App\Models\BaseUrlConfiguration;
use App\Models\InstallationSetting;
use App\Models\ServiceLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AntreanFktpCallService
{
    public function call(InstallationSetting $installation, string $noRawat, int $status = 1): array
    {
        if (!in_array($status, [1, 2], true)) {
            throw new RuntimeException('Status panggilan antrean tidak valid.');
        }

        $patient = DB::connection('clinic')->table('reg_periksa as rp')
            ->join('pasien as ps', 'ps.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->join('maping_poliklinik_pcare as mpp', 'rp.kd_poli', '=', 'mpp.kd_poli_rs')
            ->where('rp.no_rawat', $noRawat)
            ->select(['rp.no_rawat', 'rp.tgl_registrasi', 'mpp.kd_poli_pcare as kd_poli', 'ps.no_peserta'])
            ->first();

        if (!$patient) {
            return ['metadata' => ['code' => 404, 'message' => 'Data pasien tidak ditemukan']];
        }

        $callTime = CarbonImmutable::parse($patient->tgl_registrasi.' '.now('Asia/Jakarta')->format('H:i:s'), 'Asia/Jakarta');
        $payload = [
            'tanggalperiksa' => $patient->tgl_registrasi,
            'kodepoli' => $patient->kd_poli,
            'nomorkartu' => $patient->no_peserta,
            'status' => $status,
            'waktu' => $callTime->getTimestampMs(),
        ];

        $bpjs = $installation->clinic->bpjsConfiguration;
        $baseUrl = BaseUrlConfiguration::firstOrFail()->base_url_antrean;
        $timestamp = time();
        $signature = base64_encode(hash_hmac('sha256', $bpjs->cons_id.'&'.$timestamp, $bpjs->secret_key, true));
        $url = $this->callUrl($baseUrl);
        $started = microtime(true);
        $requestHeaders = [
            'X-cons-id' => $bpjs->cons_id,
            'X-timestamp' => (string) $timestamp,
            'X-signature' => $signature,
            'user_key' => $bpjs->user_key_antrol,
            'X-authorization' => 'Basic '.base64_encode($bpjs->username.':'.$bpjs->password.':'.$bpjs->kode_aplikasi),
        ];
        $debug = [
            'url' => $url,
            'headers' => [
                'x-cons-id' => $requestHeaders['X-cons-id'],
                'x-timestamp' => $requestHeaders['X-timestamp'],
                'x-signature' => $requestHeaders['X-signature'],
                'user_key' => $requestHeaders['user_key'],
            ],
            'payload' => $payload,
        ];

        try {
            $response = Http::acceptJson()->timeout(60)->withHeaders($requestHeaders)->post($url, $payload);
        } catch (\Throwable $e) {
            $this->log($installation, $noRawat, 'error', 'failed', 'Gagal menghubungi BPJS: '.$e->getMessage(), null, $started);
            return [
                'metadata' => ['code' => 500, 'message' => 'Gagal menghubungi BPJS: '.$e->getMessage()],
                'debug' => $debug,
            ];
        }

        $json = $response->json();
        if (!is_array($json)) {
            $json = ['metadata' => ['code' => $response->status(), 'message' => 'Response BPJS bukan JSON']];
        }
        $code = (int) data_get($json, 'metadata.code', $response->status());
        $message = (string) data_get($json, 'metadata.message', 'Tidak ada pesan');
        $label = $status === 2 ? 'Panggil antrean tidak hadir' : 'Panggil antrean hadir';
        $this->log($installation, $noRawat, $code === 200 ? 'success' : 'warning', $code === 200 ? 'success' : 'failed', "{$label}: {$code} | {$message}", $code, $started);
        $json['debug'] = $debug;

        return $json;
    }

    private function callUrl(string $baseUrl): string
    {
        $url = rtrim(trim($baseUrl), '/');
        if (preg_match('~/antreanfktp(?:_dev)?/antrean/panggil$~i', $url)) return $url;
        if (preg_match('~/antreanfktp(?:_dev)?/antrean/add$~i', $url)) return preg_replace('~/add$~i', '/panggil', $url);
        if (preg_match('~/antreanfktp(?:_dev)?$~i', $url)) return $url.'/antrean/panggil';
        return $url.'/antreanfktp/antrean/panggil';
    }

    private function log(InstallationSetting $installation, string $reference, string $level, string $status, string $message, ?int $code, float $started): void
    {
        ServiceLog::create(['clinic_id' => $installation->clinic_id, 'service_name' => 'antrean_fktp_panggil', 'level' => $level, 'status' => $status, 'reference' => $reference, 'message' => $message, 'response_code' => $code, 'duration_ms' => (int) ((microtime(true) - $started) * 1000)]);
    }
}
