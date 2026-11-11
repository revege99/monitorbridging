<?php

namespace App\Services;

use App\Models\BaseUrlConfiguration;
use App\Models\InstallationSetting;
use App\Models\ServiceLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AntreanFktpCancelService
{
    public function cancel(InstallationSetting $installation, string $noRawat, string $alasan): array
    {
        $patient = DB::connection('clinic')->table('reg_periksa as rp')
            ->join('pasien as ps', 'ps.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->join('maping_poliklinik_pcare as mpp', 'rp.kd_poli', '=', 'mpp.kd_poli_rs')
            ->where('rp.no_rawat', $noRawat)
            ->select(['rp.no_rawat', 'rp.tgl_registrasi', 'mpp.kd_poli_pcare as kd_poli', 'ps.no_peserta'])
            ->first();

        if (! $patient) {
            return ['metadata' => ['code' => 404, 'message' => 'Data pasien tidak ditemukan']];
        }

        $payload = [
            'tanggalperiksa' => $patient->tgl_registrasi,
            'kodepoli' => $patient->kd_poli,
            'nomorkartu' => $patient->no_peserta,
            'alasan' => $alasan,
        ];

        $bpjs = $installation->clinic->bpjsConfiguration;
        $baseUrl = BaseUrlConfiguration::firstOrFail()->base_url_antrean_batal;
        $timestamp = time();
        $signature = base64_encode(hash_hmac('sha256', $bpjs->cons_id.'&'.$timestamp, $bpjs->secret_key, true));
        $url = $baseUrl;
        if (! is_string($url) || trim($url) === '') {
            throw new RuntimeException('URL Antrean Batal belum dikonfigurasi.');
        }
        $started = microtime(true);
        $requestHeaders = [
            'X-cons-id' => $bpjs->cons_id,
            'X-timestamp' => (string) $timestamp,
            'X-signature' => $signature,
            'user_key' => $bpjs->user_key_antrol,
        ];
        $debug = [
            'url' => $url,
            'headers' => [
                'x-cons-id' => $bpjs->cons_id,
                'x-timestamp' => (string) $timestamp,
                'x-signature' => 'generated (hidden)',
                'user_key' => blank($bpjs->user_key_antrol) ? 'empty' : 'configured (hidden)',
            ],
            'payload' => $payload,
        ];

        try {
            $response = Http::acceptJson()->timeout(60)->withHeaders($requestHeaders)->post($url, $payload);
        } catch (\Throwable $e) {
            $this->log($installation, $noRawat, 'error', 'failed', 'Gagal menghubungi BPJS: '.$e->getMessage(), null, $started);

            return [
                'metadata' => ['code' => 500, 'message' => 'Gagal menghubungi BPJS.'],
                'debug' => $debug,
            ];
        }

        $json = $response->json();
        if (! is_array($json)) {
            $json = ['metadata' => ['code' => 502, 'message' => 'Response BPJS bukan JSON']];
        }
        $code = (int) data_get($json, 'metadata.code', $response->status());
        $message = (string) data_get($json, 'metadata.message', 'Tidak ada pesan');
        if ((int) data_get($json, 'metadata.code') === 200) {
            DB::connection('clinic')->transaction(function () use ($noRawat) {
                DB::connection('clinic')->table('antrean_fktp_cancellations')->insert([
                    'no_rawat' => $noRawat,
                    'status' => 'success',
                    'waktu_batal' => now('Asia/Bangkok'),
                ]);
                DB::connection('clinic')->table('reg_periksa')
                    ->where('no_rawat', $noRawat)
                    ->update(['stts' => 'Batal']);
            });
        }
        $label = 'Batal antrean';
        $this->log($installation, $noRawat, $code === 200 ? 'success' : 'warning', $code === 200 ? 'success' : 'failed', "{$label}: {$code} | {$message}", $code, $started);

        $json['debug'] = $debug;

        return $json;
    }

    private function log(InstallationSetting $installation, string $reference, string $level, string $status, string $message, ?int $code, float $started): void
    {
        ServiceLog::create(['clinic_id' => $installation->clinic_id, 'service_name' => 'antrean_fktp_batal', 'level' => $level, 'status' => $status, 'reference' => $reference, 'message' => $message, 'response_code' => $code, 'duration_ms' => (int) ((microtime(true) - $started) * 1000)]);
    }
}
