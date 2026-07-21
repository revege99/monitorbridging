<?php

namespace App\Services;

use App\Models\BaseUrlConfiguration;
use App\Models\InstallationSetting;
use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;
use RuntimeException;

class BpjsParticipantService
{
    public function lookup(InstallationSetting $installation, string $type, string $number): array
    {
        if (!in_array($type, ['nik', 'noka'], true)) throw new RuntimeException('Jenis pencarian tidak valid.');

        $bpjs = $installation->clinic->bpjsConfiguration;
        $timestamp = (string) time();
        $signature = base64_encode(hash_hmac('sha256', $bpjs->cons_id.'&'.$timestamp, $bpjs->secret_key, true));
        $url = $this->pcareBaseUrl(BaseUrlConfiguration::firstOrFail()->base_url_pcare).'peserta/'.$type.'/'.rawurlencode($number);

        try {
            $response = Http::acceptJson()->timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
                'X-cons-id' => $bpjs->cons_id,
                'X-timestamp' => $timestamp,
                'X-signature' => $signature,
                'X-authorization' => 'Basic '.base64_encode($bpjs->username.':'.$bpjs->password.':'.$bpjs->kode_aplikasi),
                'user_key' => $bpjs->user_key_pcare,
            ])->get($url);
        } catch (\Throwable $e) {
            throw new RuntimeException('Gagal menghubungi layanan BPJS.', 0, $e);
        }

        if (!$response->successful()) throw new RuntimeException('Layanan BPJS mengembalikan HTTP '.$response->status().'.');
        $json = $response->json();
        if (!is_array($json)) throw new RuntimeException('Respons BPJS tidak valid.');

        $metadata = $json['metaData'] ?? $json['metadata'] ?? null;
        if (!is_array($metadata)) throw new RuntimeException('Metadata respons BPJS tidak tersedia.');
        if ((int) ($metadata['code'] ?? 0) !== 200) throw new RuntimeException((string) ($metadata['message'] ?? 'Permintaan BPJS gagal.'));

        $decrypted = $this->decrypt($bpjs->cons_id.$bpjs->secret_key.$timestamp, (string) ($json['response'] ?? ''));
        if ($decrypted === false || $decrypted === '') throw new RuntimeException('Data peserta BPJS tidak dapat dibaca.');
        $decompressed = LZString::decompressFromEncodedURIComponent($decrypted);
        if (!is_string($decompressed) || $decompressed === '') throw new RuntimeException('Data peserta BPJS tidak dapat diproses.');
        $participant = json_decode($decompressed, true);
        if (!is_array($participant)) throw new RuntimeException('Format data peserta BPJS tidak dikenali.');

        return $this->normalize($participant, $type, $number);
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

    private function normalize(array $participant, string $type, string $number): array
    {
        $nik = trim((string) ($participant['noKTP'] ?? $participant['nik'] ?? ''));
        $card = trim((string) ($participant['noKartu'] ?? $participant['noka'] ?? ''));
        if ($type === 'nik' && ($nik === '' || $nik === '-')) $nik = $number;
        if ($type === 'noka' && ($card === '' || $card === '-')) $card = $number;
        $activeValue = $participant['aktif'] ?? data_get($participant, 'statusPeserta.keterangan');
        $active = is_bool($activeValue) ? $activeValue : in_array(mb_strtolower(trim((string) $activeValue)), ['1', 'true', 'aktif', 'active'], true);
        $gender = mb_strtoupper(trim((string) ($participant['sex'] ?? $participant['jenisKelamin'] ?? '')));
        $gender = in_array($gender, ['P', 'PEREMPUAN', 'WANITA'], true) ? 'P' : 'L';

        return [
            'nik' => $nik,
            'noka' => $card,
            'nama' => trim((string) ($participant['nama'] ?? '')),
            'jenis_kelamin' => $gender,
            'tanggal_lahir' => $this->date((string) ($participant['tglLahir'] ?? '')),
            'no_hp' => trim((string) ($participant['noHP'] ?? $participant['noTelepon'] ?? '')),
            'alamat' => trim((string) ($participant['alamat'] ?? '')),
            'aktif' => $active,
            'status' => trim((string) (data_get($participant, 'statusPeserta.keterangan') ?? ($active ? 'AKTIF' : 'TIDAK AKTIF'))),
        ];
    }

    private function date(string $value): ?string
    {
        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, trim($value));
            if ($date && $date->format($format) === trim($value)) return $date->format('Y-m-d');
        }
        return null;
    }
}
