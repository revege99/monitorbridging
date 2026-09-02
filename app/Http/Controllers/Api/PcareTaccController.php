<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActiveInstallation;
use App\Services\PcareDiagnosisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PcareTaccController extends Controller
{
    public function __invoke(Request $request, ActiveInstallation $active, PcareDiagnosisService $service): JsonResponse
    {
        $configuredToken = (string) config('services.khanza.integration_token');
        $providedToken = (string) $request->header('X-Integration-Token');
        if ($configuredToken === '' || !hash_equals($configuredToken, $providedToken)) {
            return response()->json(['status' => false, 'message' => 'Token integrasi tidak valid.'], 401);
        }

        $careNumber = trim((string) $request->input('no_rawat'));
        if ($careNumber === '') return response()->json(['status' => false, 'message' => 'no_rawat kosong.'], 400);
        if (mb_strlen($careNumber) > 17) return response()->json(['status' => false, 'message' => 'Format no_rawat tidak valid.'], 400);

        try {
            $installation = $active->resolve();
            $active->configureClinicDatabase($installation);
            return response()->json(
                $service->syncNonSpecialist($installation, $careNumber),
                200,
                [],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'no_rawat' => $careNumber], 422);
        }
    }
}
