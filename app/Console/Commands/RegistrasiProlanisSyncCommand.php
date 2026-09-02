<?php

namespace App\Console\Commands;

use App\Models\ServiceRun;
use App\Services\ActiveInstallation;
use App\Services\RegistrasiProlanisSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RegistrasiProlanisSyncCommand extends Command
{
    protected $signature = 'service:registrasi-prolanis-sync {--once : Jalankan satu kali scan} {--interval=5 : Jeda scan dalam detik}';
    protected $description = 'Sinkronkan data Prolanis dan PRB untuk registrasi BPJS hari ini';

    public function handle(ActiveInstallation $active, RegistrasiProlanisSyncService $service): int
    {
        $lock = Cache::lock('service:registrasi-prolanis-sync', 86400);
        if (!$lock->get()) {
            $this->error('Service yang sama sudah berjalan.');
            return self::FAILURE;
        }

        $run = null;

        try {
            $installation = $active->resolve();
            $active->configureClinicDatabase($installation);
            $run = ServiceRun::create([
                'clinic_id' => $installation->clinic_id,
                'service_name' => 'registrasi_prolanis_sync',
                'status' => 'running',
                'started_at' => now(),
                'last_heartbeat_at' => now(),
            ]);

            $this->newLine();
            $this->info('SERVICE SINKRON REGISTRASI PROLANIS / PRB');
            $this->line("Klinik: {$installation->clinic->name}");

            do {
                $run->update(['last_heartbeat_at' => now()]);
                $service->scan($installation, $run, function (string $level, string $message): void {
                    $line = '['.now()->format('H:i:s').'] '.mb_strtoupper($level).' '.$message;
                    $level === 'error' ? $this->error($line) : $this->line($line);
                });

                if ($this->option('once')) break;
                sleep(max(1, (int) $this->option('interval')));
            } while (true);

            $run->update(['status' => 'completed', 'finished_at' => now(), 'message' => 'Service selesai.']);
            return self::SUCCESS;
        } catch (Throwable $e) {
            if ($run) $run->update(['status' => 'failed', 'finished_at' => now(), 'message' => $e->getMessage()]);
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
