<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActiveInstallation;
use App\Services\AntreanFktpCallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AntreanFktpCallController extends Controller
{
    public function __invoke(Request $request, ActiveInstallation $active, AntreanFktpCallService $service): JsonResponse
    {
        $configuredToken = (string) config('services.khanza.integration_token');
        $providedToken = (string) $request->header('X-Integration-Token');
        if ($configuredToken === '' || !hash_equals($configuredToken, $providedToken)) {
            return response()->json(['metadata' => ['code' => 401, 'message' => 'Token integrasi tidak valid']], 401);
        }

        $noRawat = trim((string) $request->input('no_rawat'));
        if ($noRawat === '') {
            return response()->json(['metadata' => ['code' => 400, 'message' => 'no_rawat kosong']], 400);
        }
        if (mb_strlen($noRawat) > 30) {
            return response()->json(['metadata' => ['code' => 400, 'message' => 'Format no_rawat tidak valid']], 400);
        }
        try {
            $installation = $active->resolve();
            $active->configureClinicDatabase($installation);
            $status = (int) $request->route('queue_status', 1);
            $result = $service->call($installation, $noRawat, $status);
            $debug = $result['debug'] ?? [];
            unset($result['debug']);

            $response = response()->json(
                $result,
                200,
                [],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );

            $debugHeaders = [
                'X-BPJS-Debug-Url' => data_get($debug, 'url'),
                'X-BPJS-Debug-Cons-Id' => data_get($debug, 'headers.x-cons-id'),
                'X-BPJS-Debug-Timestamp' => data_get($debug, 'headers.x-timestamp'),
                'X-BPJS-Debug-Signature' => data_get($debug, 'headers.x-signature'),
                'X-BPJS-Debug-User-Key' => data_get($debug, 'headers.user_key'),
                'X-BPJS-Payload-Tanggal' => data_get($debug, 'payload.tanggalperiksa'),
                'X-BPJS-Payload-Kode-Poli' => data_get($debug, 'payload.kodepoli'),
                'X-BPJS-Payload-Nomor-Kartu' => data_get($debug, 'payload.nomorkartu'),
                'X-BPJS-Payload-Status' => data_get($debug, 'payload.status'),
                'X-BPJS-Payload-Waktu' => data_get($debug, 'payload.waktu'),
            ];

            foreach ($debugHeaders as $name => $value) {
                if ($value !== null) $response->headers->set($name, (string) $value);
            }

            return $response;
        } catch (Throwable $e) {
            report($e);
            return response()->json(['metadata' => ['code' => 500, 'message' => $e->getMessage()]], 500);
        }
    }
}
