<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use App\Services\ClinicContext;
use App\Services\LocalClinic;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ConfigureClinicDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() && $request->user()->is_active, 403);
        $clinic = Clinic::with('database')->first();
        view()->share('activeClinic', $clinic);
        $centralOnlyRoute = $request->routeIs('setup.*', 'service-monitor.*', 'logout', 'account.password.update');
        if ($clinic && ! $clinic->is_active && ! $centralOnlyRoute) {
            abort(503, 'Profil klinik aplikasi sedang nonaktif.');
        }
        if (! $clinic?->database && ! $centralOnlyRoute) {
            if ($request->user()->isSuperadmin()) {
                return redirect()->route('setup.index', ['tab' => $clinic ? 'database' : 'clinics']);
            }
            abort(503, 'Database SIMRS aplikasi belum dikonfigurasi. Hubungi superadmin.');
        }
        if ($clinic?->database && ! $centralOnlyRoute) {
            app(LocalClinic::class)->configureClinicDatabase(new ClinicContext($clinic));
            DB::setDefaultConnection('clinic');
        }

        return $next($request);
    }
}
