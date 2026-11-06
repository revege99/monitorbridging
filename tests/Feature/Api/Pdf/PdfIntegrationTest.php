<?php

namespace Tests\Feature\Api\Pdf;

use App\Http\Middleware\AuthenticatePdfIntegration;
use App\Http\Requests\Api\Pdf\PrintPdfRequest;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PdfIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.khanza.integration_token' => 'test-pdf-token']);

        // Probe hanya didaftarkan dalam test, tanpa endpoint PDF palsu di produksi.
        Route::post('/api/integration/pdf/test-probe', function (PrintPdfRequest $request) {
            return response()->json($request->validated());
        })->middleware(['api', AuthenticatePdfIntegration::class]);
    }

    public function test_missing_wrong_or_query_token_is_rejected(): void
    {
        $url = '/api/integration/pdf/test-probe';

        $this->postJson($url, ['no_rawat' => '2026/11/06/000001'])->assertUnauthorized();
        $this->withHeader('X-Integration-Token', 'wrong')->postJson($url)->assertUnauthorized();
        $this->postJson($url.'?token=test-pdf-token')->assertUnauthorized();
    }

    public function test_unconfigured_token_is_rejected(): void
    {
        config(['services.khanza.integration_token' => '']);

        $this->withHeader('X-Integration-Token', 'test-pdf-token')
            ->postJson('/api/integration/pdf/test-probe')->assertUnauthorized();
    }

    public function test_invalid_care_numbers_are_rejected(): void
    {
        $this->withHeader('X-Integration-Token', 'test-pdf-token');

        foreach ([null, '', ['invalid'], 123, str_repeat('1', 18)] as $number) {
            $this->postJson('/api/integration/pdf/test-probe', ['no_rawat' => $number])
                ->assertUnprocessable()->assertJsonValidationErrors('no_rawat');
        }
    }

    public function test_care_number_with_slashes_is_accepted_in_json_and_form(): void
    {
        $this->withHeader('X-Integration-Token', 'test-pdf-token');
        $body = ['no_rawat' => '2026/11/06/000001'];

        $response = $this->postJson('/api/integration/pdf/test-probe', $body)
            ->assertOk()->assertExactJson($body);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->post('/api/integration/pdf/test-probe', $body)->assertOk()->assertExactJson($body);
    }
}
