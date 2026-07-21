<?php
namespace App\Http\Controllers;
use App\Models\BaseUrlConfiguration;
use App\Models\Clinic;
use App\Models\InstallationSetting;
use App\Models\ServiceLog;
use App\Models\ServiceRun;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class InstallationController extends Controller
{
    private function authorizeSuperadmin(Request $request): void { abort_unless($request->user()->isSuperadmin(), 403); }
    public function index(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $installation = InstallationSetting::with('clinic')->first();
        if ($installation?->clinic) {
            $request->session()->put('active_clinic_id', $installation->clinic_id);
            view()->share('activeClinic', $installation->clinic);
        }
        return view('installation.index', ['clinics'=>Clinic::with(['database','bpjsConfiguration'])->orderBy('name')->get(),'installation'=>$installation,'pageDateLabel'=>now()->translatedFormat('l, d F Y')]);
    }
    public function activate(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $clinic = Clinic::with(['database','bpjsConfiguration'])->findOrFail($request->validate(['clinic_id'=>['required','exists:central.clinics,id']])['clinic_id']);
        $missing = collect(['database'=>'Database SIMRS','bpjsConfiguration'=>'Konfigurasi BPJS'])->filter(fn($label,$relation)=>!$clinic->{$relation})->values();
        if (!BaseUrlConfiguration::exists()) $missing->push('Base URL BPJS global');
        if ($missing->isNotEmpty()) return back()->withErrors(['clinic_id'=>'Belum lengkap: '.$missing->join(', ')]);
        InstallationSetting::query()->delete();
        InstallationSetting::create(['clinic_id'=>$clinic->id,'installation_uuid'=>(string) Str::uuid(),'is_activated'=>true,'activated_at'=>now(),'activated_by'=>$request->user()->id]);
        $request->session()->put('active_clinic_id', $clinic->id);
        return back()->with('success', "Instalasi aktif untuk {$clinic->name}.");
    }
    public function monitor(Request $request)
    {
        $installation = InstallationSetting::with('clinic')->where('is_activated',true)->first();
        if ($installation?->clinic) {
            $request->session()->put('active_clinic_id', $installation->clinic_id);
            view()->share('activeClinic', $installation->clinic);
        }
        $run = $installation ? ServiceRun::where('clinic_id',$installation->clinic_id)->where('service_name','antrean_fktp_add')->latest()->first() : null;
        $logs = $installation ? ServiceLog::where('clinic_id',$installation->clinic_id)->where('service_name','antrean_fktp_add')->latest()->limit(150)->get()->reverse()->values() : collect();
        return view('service-monitor.index', compact('installation','run','logs') + ['pageDateLabel'=>now()->translatedFormat('l, d F Y')]);
    }
    public function logs(Request $request)
    {
        $installation = InstallationSetting::where('is_activated',true)->firstOrFail();
        $logs = ServiceLog::where('clinic_id',$installation->clinic_id)->where('service_name','antrean_fktp_add')->where('id','>',(int)$request->query('after_id',0))->orderBy('id')->limit(200)->get();
        $run = ServiceRun::where('clinic_id',$installation->clinic_id)->where('service_name','antrean_fktp_add')->latest()->first();
        return response()->json(['logs'=>$logs,'run'=>$run,'online'=>$run?->last_heartbeat_at?->gt(now()->subSeconds(20)) ?? false]);
    }
}
