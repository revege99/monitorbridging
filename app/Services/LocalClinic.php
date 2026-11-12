<?php

namespace App\Services;

use App\Models\BaseUrlConfiguration;
use App\Models\Clinic;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocalClinic
{
    public function resolve(): ClinicContext
    {
        $context = $this->resolveForPdf();
        if (! $context->clinic->bpjsConfiguration) {
            throw new RuntimeException('Konfigurasi BPJS belum tersedia.');
        }
        if (! BaseUrlConfiguration::exists()) {
            throw new RuntimeException('Base URL BPJS belum tersedia.');
        }

        return $context;
    }

    public function resolveForPdf(): ClinicContext
    {
        $clinic = Clinic::with('database')->first();
        if (! $clinic) {
            throw new RuntimeException('Profil klinik aplikasi belum tersedia.');
        }
        if (! $clinic->is_active) {
            throw new RuntimeException('Profil klinik aplikasi sedang nonaktif.');
        }
        if (! $clinic->database) {
            throw new RuntimeException('Konfigurasi database SIMRS belum tersedia.');
        }

        return new ClinicContext($clinic);
    }

    public function configureClinicDatabase(ClinicContext $context): void
    {
        $database = $context->clinic->database;
        $configuration = ['driver' => $database->driver, 'host' => $database->host,
            'port' => $database->port, 'database' => $database->database_name,
            'username' => $database->username, 'password' => $database->password,
            'charset' => $database->charset, 'collation' => $database->collation,
            'prefix' => '', 'prefix_indexes' => true, 'strict' => true, 'engine' => null];
        // Tidak membuka ulang koneksi pada worker jika konfigurasi tidak berubah.
        if (config('database.connections.clinic') !== $configuration) {
            config(['database.connections.clinic' => $configuration]);
            DB::purge('clinic');
        }
        // Koneksi dibuka secara lazy saat query pertama, tanpa koneksi uji tambahan.
    }
}
