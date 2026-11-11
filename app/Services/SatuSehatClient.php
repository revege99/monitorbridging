<?php

namespace App\Services;

use App\Models\SatuSehatBaseUrlConfiguration;
use App\Models\SatuSehatConfiguration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SatuSehatClient
{
    private ?string $token = null;

    private int $expiresAt = 0;

    private SatuSehatConfiguration $credentials;

    private SatuSehatBaseUrlConfiguration $urls;

    public function configure(SatuSehatConfiguration $credentials, SatuSehatBaseUrlConfiguration $urls): void
    {
        foreach (['client_id', 'client_secret', 'organization_id', 'location_id'] as $field) {
            if (blank($credentials->$field)) {
                throw new SatuSehatConfigurationException("Konfigurasi Satu Sehat belum lengkap: {$field}.");
            }
        }
        foreach (['auth_url', 'fhir_url'] as $field) {
            if (! filter_var($urls->$field, FILTER_VALIDATE_URL) || parse_url($urls->$field, PHP_URL_SCHEME) !== 'https') {
                throw new SatuSehatConfigurationException("{$field} Satu Sehat harus berupa URL HTTPS.");
            }
        }
        $this->credentials = $credentials;
        $this->urls = $urls;
        $this->token = null;
    }

    private function request(): PendingRequest
    {
        if ($this->token === null || time() >= $this->expiresAt) {
            $response = Http::asForm()->withQueryParameters(['grant_type' => 'client_credentials'])->connectTimeout(10)->timeout(30)
                ->withOptions(['allow_redirects' => false])
                ->post($this->urls->auth_url, [
                    'client_id' => $this->credentials->client_id,
                    'client_secret' => $this->credentials->client_secret,
                ]);
            if (! $response->successful() || ! is_string($response->json('access_token')) || $response->json('access_token') === '') {
                throw new RuntimeException('Autentikasi Satu Sehat gagal (HTTP '.$response->status().').');
            }
            $this->token = $response->json('access_token');
            $this->expiresAt = time() + max(1, (int) $response->json('expires_in', 300) - 60);
        }

        return Http::withToken($this->token)->acceptJson()->contentType('application/fhir+json')
            ->connectTimeout(10)->timeout(60)->withOptions(['allow_redirects' => false]);
    }

    public function search(string $type, array $parameters): ?array
    {
        $response = $this->request()->get(rtrim($this->urls->fhir_url, '/').'/'.$type, $parameters);
        if ($response->status() === 401) {
            $this->token = null;
            $response = $this->request()->get(rtrim($this->urls->fhir_url, '/').'/'.$type, $parameters);
        }
        if (! $response->successful() || $response->json('resourceType') !== 'Bundle') {
            throw new RuntimeException("Pencarian {$type} gagal (HTTP {$response->status()}).");
        }
        $entries = $response->json('entry', []);
        if (count($entries) > 1 || (int) $response->json('total', count($entries)) > 1) {
            throw new RuntimeException("Pencarian {$type} menghasilkan lebih dari satu data; perlu rekonsiliasi.");
        }

        return $entries[0]['resource'] ?? null;
    }

    public function authenticate(): void
    {
        $this->request();
    }

    public function create(string $type, array $payload): Response
    {
        // Jangan retry POST: timeout tidak berarti server gagal menyimpan resource.
        $response = $this->request()->post(rtrim($this->urls->fhir_url, '/').'/'.$type, $payload);
        if ($response->status() === 401) {
            $this->token = null;
        }

        return $response;
    }
}
