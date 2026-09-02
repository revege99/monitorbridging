<?php

namespace App\Services;

use App\Models\BaseUrlConfiguration;
use App\Models\InstallationSetting;
use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;
use RuntimeException;
use Throwable;

class PcareOnlineRegistrationService
{
    public function register(InstallationSetting $installation, string $cardNumber, ?array &$requestDebug = null): array
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        if (strlen($cardNumber) !== 13) throw new RuntimeException('Nomor kartu BPJS harus terdiri dari 13 digit.');

        $bpjs = $installation->clinic->bpjsConfiguration;
        if (!$bpjs) throw new RuntimeException('Konfigurasi BPJS klinik belum tersedia.');

        $payload = [
            'kdProviderPeserta' => '03730101',
            'tglDaftar' => now('Asia/Jakarta')->format('d-m-Y'),
            'noKartu' => $cardNumber,
            'kdPoli' => '999',
            'keluhan' => 'kunjungan online',
            'kunjSakit' => false,
            'sistole' => 0,
            'diastole' => 0,
            'beratBadan' => 0,
            'tinggiBadan' => 0,
            'respRate' => 0,
            'lingkarPerut' => 0,
            'heartRate' => 0,
            'rujukBalik' => 0,
            'kdTkp' => '10',
        ];

        $timestamp = (string) time();
        $signature = base64_encode(hash_hmac('sha256', $bpjs->cons_id.'&'.$timestamp, $bpjs->secret_key, true));
        $url = $this->pcareBaseUrl(BaseUrlConfiguration::firstOrFail()->base_url_pcare).'pendaftaran';

        $headers = [
            'Content-Type' => 'text/plain',
            'Accept' => 'application/json',
            'X-cons-id' => $bpjs->cons_id,
            'X-timestamp' => $timestamp,
            'X-signature' => $signature,
            'X-authorization' => 'Basic '.base64_encode($bpjs->username.':'.$bpjs->password.':'.$bpjs->kode_aplikasi),
            'user_key' => $bpjs->user_key_pcare,
        ];

        if (config('app.debug')) {
            $requestDebug = ['method' => 'POST', 'url' => $url, 'headers' => $headers, 'payload' => $payload];
        }

        try {
            $response = Http::timeout(30)->withHeaders([
                ...$headers,
            ])->withBody(json_encode($payload, JSON_UNESCAPED_SLASHES), 'text/plain')->post($url);
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal menghubungi layanan pendaftaran PCare.', 0, $e);
        }

        $json = $response->json();

        if (config('app.debug') && $requestDebug !== null) {
            $requestDebug['response'] = [
                'http_status' => $response->status(),
                'body' => is_array($json) ? $json : $response->body(),
            ];
        }

        if (!$response->successful()) {
            $message = is_array($json)
                ? (string) (data_get($json, 'metaData.message') ?? data_get($json, 'metadata.message') ?? '')
                : '';

            if ($message === '') {
                $message = trim((string) preg_replace('/\s+/', ' ', strip_tags($response->body())));
                if ($message !== '') $message = mb_strimwidth($message, 0, 500, '...');
            }

            throw new RuntimeException(
                'Pendaftaran PCare ditolak (HTTP '.$response->status().'): '.($message !== '' ? $message : 'BPJS tidak memberikan keterangan.')
            );
        }

        if (!is_array($json)) throw new RuntimeException('Respons pendaftaran PCare tidak valid.');

        $metadata = $json['metaData'] ?? $json['metadata'] ?? null;
        if (!is_array($metadata)) throw new RuntimeException('Metadata respons pendaftaran PCare tidak tersedia.');
        if (!in_array((int) ($metadata['code'] ?? 0), [200, 201], true)) {
            throw new RuntimeException((string) ($metadata['message'] ?? 'Pendaftaran PCare gagal.'));
        }

        return [
            'metadata' => $metadata,
            'response' => $this->decodedResponse($json['response'] ?? null, $bpjs->cons_id.$bpjs->secret_key.$timestamp),
            'payload' => $payload,
        ];
    }

    private function decodedResponse(mixed $response, string $key): mixed
    {
        if ($response === null || $response === '') return null;
        if (is_array($response)) return $response;

        $keyHash = hex2bin(hash('sha256', $key));
        $decrypted = openssl_decrypt(base64_decode((string) $response), 'AES-256-CBC', $keyHash, OPENSSL_RAW_DATA, substr((string) $keyHash, 0, 16));
        if ($decrypted === false || $decrypted === '') return $response;
        $decompressed = LZString::decompressFromEncodedURIComponent($decrypted);
        if (!is_string($decompressed) || $decompressed === '') return $decrypted;
        return json_decode($decompressed, true) ?? $decompressed;
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
