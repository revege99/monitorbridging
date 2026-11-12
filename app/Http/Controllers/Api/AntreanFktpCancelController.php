<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocalClinic;
use App\Services\AntreanFktpCancelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AntreanFktpCancelController extends Controller
{
    public function __invoke(Request $request, LocalClinic $active, AntreanFktpCancelService $service): JsonResponse
    {
        $configuredToken = (string) config('services.khanza.integration_token');
        $providedToken = (string) $request->header('X-Integration-Token');
        if ($configuredToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json(['metadata' => ['code' => 401, 'message' => 'Token integrasi tidak valid']], 401);
        }

        $validator = validator($request->all(), [
            'no_rawat' => ['required', 'string', 'max:17'],
            'alasan' => ['required', 'string', 'max:1000'],
        ]);
        if ($validator->fails()) {
            return response()->json(['metadata' => ['code' => 400, 'message' => $validator->errors()->first()]], 400);
        }

        try {
            $installation = $active->resolve();
            $active->configureClinicDatabase($installation);
            $data = $validator->validated();

            $result = $service->cancel($installation, trim($data['no_rawat']), trim($data['alasan']));
            $debug = $result['debug'] ?? [];
            unset($result['debug']);
            $response = response()->json(
                $result,
                200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
            $headers = [
                'X-BPJS-Debug-Url' => data_get($debug, 'url'),
                'X-BPJS-Debug-Cons-Id' => data_get($debug, 'headers.x-cons-id'),
                'X-BPJS-Debug-Timestamp' => data_get($debug, 'headers.x-timestamp'),
                'X-BPJS-Debug-Signature' => data_get($debug, 'headers.x-signature'),
                'X-BPJS-Debug-User-Key' => data_get($debug, 'headers.user_key'),
                'X-BPJS-Payload-Tanggal' => data_get($debug, 'payload.tanggalperiksa'),
                'X-BPJS-Payload-Kode-Poli' => data_get($debug, 'payload.kodepoli'),
                'X-BPJS-Payload-Nomor-Kartu' => data_get($debug, 'payload.nomorkartu'),
            ];
            if (isset($debug['payload']['alasan'])) {
                // Encoding menjaga header valid untuk alasan dengan Unicode/baris baru.
                $headers['X-BPJS-Payload-Alasan'] = rawurlencode($debug['payload']['alasan']);
            }
            foreach ($headers as $name => $value) {
                if ($value !== null) {
                    $response->headers->set($name, (string) $value);
                }
            }

            return $response->header('Cache-Control', 'private, no-store');
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['metadata' => ['code' => 500, 'message' => 'Pembatalan antrean gagal diproses. Periksa konfigurasi dan log layanan.']], 500);
        }
    }
}
