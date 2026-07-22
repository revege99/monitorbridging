<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ConfigureClinicDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && $user->is_active, 403);

        $clinicId = $user->isSuperadmin() ? $request->session()->get('active_clinic_id') : $user->clinic_id;
        $clinic = $clinicId ? Clinic::with('database')->where('is_active', true)->find($clinicId) : null;

        if (!$clinic && $user->isSuperadmin()) {
            $clinic = Clinic::with('database')->where('is_active', true)->orderBy('name')->first();
            if ($clinic) $request->session()->put('active_clinic_id', $clinic->id);
        }

        view()->share('availableClinics', $user->isSuperadmin() ? Clinic::where('is_active', true)->orderBy('name')->get() : collect([$user->clinic])->filter());
        view()->share('activeClinic', $clinic);

        $centralOnlyRoute = $request->routeIs('setup.*', 'service-monitor.*', 'logout', 'clinics.select', 'account.password.update');

        if (!$clinic?->database && !$centralOnlyRoute) {
            if ($user->isSuperadmin()) return redirect()->route('setup.index', ['tab' => 'database']);
            abort(503, 'Database klinik belum dikonfigurasi. Hubungi superadmin.');
        }

        if ($clinic?->database && !$centralOnlyRoute) {
            $database = $clinic->database;
            config(['database.connections.clinic' => [
                'driver' => $database->driver,
                'host' => $database->host,
                'port' => $database->port,
                'database' => $database->database_name,
                'username' => $database->username,
                'password' => $database->password,
                'charset' => $database->charset,
                'collation' => $database->collation,
                'prefix' => '', 'prefix_indexes' => true, 'strict' => true, 'engine' => null,
            ]]);
            DB::purge('clinic');
            DB::setDefaultConnection('clinic');
        }

        return $next($request);
    }
}
