<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePdfIntegration
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('services.khanza.integration_token');
        $providedToken = (string) $request->header('X-Integration-Token');

        if ($configuredToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json(['message' => 'Token integrasi tidak valid.'], 401);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
