<?php

namespace App\Console\Commands;

use App\Models\ServiceRun;
use App\Services\LocalClinic;
use App\Services\SatuSehatConfigurationException;
use App\Services\SatuSehatService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class SatuSehatCommand extends Command
{
    protected $signature = 'service:satu-sehat
        {--once : Jalankan satu kali scan}
        {--check : Periksa konfigurasi dan tabel kontrol tanpa mengirim data}
        {--interval=30 : Jeda scan dalam detik, minimal 5}
        {--from= : Tanggal awal YYYY-MM-DD, default kemarin WIB}
        {--to= : Tanggal akhir YYYY-MM-DD, default hari ini WIB}';

    protected $description = 'Kirim resource Satu Sehat untuk klinik aplikasi';

    private bool $stopping = false;

    public function handle(LocalClinic $active, SatuSehatService $service): int
    {
        $run = null;
        $handle = null;
        $locked = false;
        try {
            $interval = filter_var($this->option('interval'), FILTER_VALIDATE_INT);
            if ($interval === false || $interval < 5 || $interval > 3600) {
                $this->error('Interval harus angka 5 sampai 3600 detik.');

                return self::FAILURE;
            }
            $this->dates();
            // Konteks aplikasi tanpa mewajibkan kredensial/base URL BPJS.
            $installation = $active->resolveForPdf();
            $active->configureClinicDatabase($installation);
            $path = storage_path('framework/satu-sehat.lock');
            $handle = fopen($path, 'c+');
            if (! $handle || ! flock($handle, LOCK_EX | LOCK_NB)) {
                $this->error('Service Satu Sehat untuk klinik ini sudah berjalan atau file lock tidak dapat dibuka.');

                return self::FAILURE;
            }
            $locked = true;
            $service->configure($installation);
            if ($this->option('check')) {
                $this->info('Konfigurasi dan tabel kontrol tersedia. Tidak ada data yang dikirim.');

                return self::SUCCESS;
            }
            if (extension_loaded('pcntl')) {
                $this->trap([SIGTERM, SIGINT], function () {
                    $this->stopping = true;
                });
            }
            $run = ServiceRun::create([ 'service_name' => 'satu_sehat',
                'status' => 'running', 'started_at' => now(), 'last_heartbeat_at' => now()]);
            $this->info('SERVICE SATU SEHAT | '.$installation->clinic->name);
            do {
                [$from, $to] = $this->dates();
                $run->update(['last_heartbeat_at' => now()]);
                $this->line("Scan {$from} sampai {$to} (WIB)");
                try {
                    $service->scan($installation, $run, $from, $to, function ($level, $message) {
                        $this->line('['.now('Asia/Jakarta')->format('H:i:s').'] '.strtoupper($level).' '.$message);
                    });
                } catch (Throwable $e) {
                    $run->increment('failed');
                    $run->update(['message' => 'Scan gagal; periksa konfigurasi, autentikasi, skema dan koneksi.']);
                    $this->error('Scan gagal; periksa konfigurasi, autentikasi, skema dan koneksi.');
                    if ($this->option('once')) {
                        $run->update(['status' => 'failed', 'finished_at' => now()]);

                        return self::FAILURE;
                    }
                }
                if ($this->option('once') || $this->stopping) {
                    break;
                }
                for ($seconds = 0; $seconds < $interval && ! $this->stopping; $seconds++) {
                    if ($seconds % 5 === 0) {
                        $run->update(['last_heartbeat_at' => now()]);
                    }
                    sleep(1);
                }
            } while (! $this->stopping);
            $run->update(['status' => 'completed', 'finished_at' => now(), 'message' => 'Service selesai.']);

            return self::SUCCESS;
        } catch (Throwable $e) {
            if ($run) {
                $run->update(['status' => 'failed', 'finished_at' => now(), 'message' => 'Service gagal dimulai/dilanjutkan.']);
            }
            $this->error($e instanceof SatuSehatConfigurationException || $e instanceof \InvalidArgumentException
                ? $e->getMessage()
                : 'Service gagal. Periksa profil klinik, konfigurasi Satu Sehat, file lock dan tabel kontrol SIMRS.');

            return self::FAILURE;
        } finally {
            if (is_resource($handle)) {
                if ($locked) {
                    flock($handle, LOCK_UN);
                }
                fclose($handle);
            }
        }
    }

    private function dates(): array
    {
        $dates = [];
        foreach (['from', 'to'] as $option) {
            $defaultDate = now('Asia/Jakarta');
            if ($option === 'from') {
                $defaultDate->subDay();
            }
            $value = $this->option($option) ?: $defaultDate->toDateString();
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)
                || CarbonImmutable::parse($value, 'Asia/Jakarta')->format('Y-m-d') !== $value) {
                throw new \InvalidArgumentException('Format tanggal harus YYYY-MM-DD.');
            }
            $dates[] = $value;
        }
        if ($dates[0] > $dates[1] || $dates[1] > now('Asia/Jakarta')->toDateString()) {
            throw new \InvalidArgumentException('Rentang tanggal tidak valid.');
        }

        return $dates;
    }
}
