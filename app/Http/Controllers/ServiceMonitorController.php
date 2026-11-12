<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\ServiceLog;
use App\Models\ServiceRun;
use App\Services\ClinicContext;
use Illuminate\Http\Request;

class ServiceMonitorController extends Controller
{
    public function monitor(Request $request)
    {
        $clinic = Clinic::first();
        $installation = $clinic ? new ClinicContext($clinic) : null;
        $run = ServiceRun::where('service_name', 'antrean_fktp_add')->latest()->first();
        $logs = ServiceLog::where('service_name', 'antrean_fktp_add')->latest()->limit(150)->get()->reverse()->values();

        return view('service-monitor.index', compact('installation', 'run', 'logs') + ['pageDateLabel' => now()->translatedFormat('l, d F Y')]);
    }

    public function logs(Request $request)
    {
        $logs = ServiceLog::where('service_name', 'antrean_fktp_add')->where('id', '>', (int) $request->query('after_id', 0))
            ->orderBy('id')->limit(200)->get();
        $run = ServiceRun::where('service_name', 'antrean_fktp_add')->latest()->first();

        return response()->json(['logs' => $logs, 'run' => $run,
            'online' => $run?->status === 'running' && ($run?->last_heartbeat_at?->gt(now()->subSeconds(20)) ?? false)]);
    }
}
