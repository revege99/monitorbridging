<?php
namespace App\Services;
use App\Models\BaseUrlConfiguration;
use App\Models\InstallationSetting;
use Illuminate\Support\Facades\DB;
use RuntimeException;
class ActiveInstallation
{
    public function resolve(): InstallationSetting
    {
        $installation = InstallationSetting::with(['clinic.database','clinic.bpjsConfiguration'])->where('is_activated', true)->first();
        if (!$installation?->clinic) throw new RuntimeException('Instalasi belum diaktifkan untuk klinik mana pun.');
        $clinic = $installation->clinic;
        if (!$clinic->is_active) throw new RuntimeException('Klinik yang diaktifkan sedang nonaktif.');
        if (!$clinic->database) throw new RuntimeException('Konfigurasi database SIMRS belum tersedia.');
        if (!$clinic->bpjsConfiguration) throw new RuntimeException('Konfigurasi BPJS belum tersedia.');
        if (!BaseUrlConfiguration::exists()) throw new RuntimeException('Base URL BPJS global belum tersedia.');
        return $installation;
    }
    public function configureClinicDatabase(InstallationSetting $installation): void
    {
        $d = $installation->clinic->database;
        config(['database.connections.clinic' => ['driver'=>$d->driver,'host'=>$d->host,'port'=>$d->port,'database'=>$d->database_name,'username'=>$d->username,'password'=>$d->password,'charset'=>$d->charset,'collation'=>$d->collation,'prefix'=>'','prefix_indexes'=>true,'strict'=>true,'engine'=>null]]);
        DB::purge('clinic'); DB::connection('clinic')->getPdo();
    }
}
