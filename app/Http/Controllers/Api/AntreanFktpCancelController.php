<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActiveInstallation;
use App\Services\AntreanFktpCancelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AntreanFktpCancelController extends Controller
{
    public function __invoke(Request $request, ActiveInstallation $active, AntreanFktpCancelService $service): JsonResponse
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

            return response()->json(
                $service->cancel($installation, trim($data['no_rawat']), trim($data['alasan'])),
                200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['metadata' => ['code' => 500, 'message' => 'Pembatalan antrean gagal diproses. Periksa konfigurasi dan log layanan.']], 500);
        }
    }
}
