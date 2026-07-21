<?php
namespace App\Console\Commands;
use App\Models\ServiceRun;
use App\Services\ActiveInstallation;
use App\Services\AntreanFktpAddService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;
class AntreanFktpAddCommand extends Command
{
    protected $signature='service:antrean-fktp-add {--once : Jalankan satu kali scan} {--interval=5 : Jeda scan dalam detik}';
    protected $description='Kirim add antrean FKTP BPJS untuk klinik instalasi aktif';
    public function handle(ActiveInstallation $active,AntreanFktpAddService $service): int
    {
        $lock=Cache::lock('service:antrean-fktp-add',86400); if(!$lock->get()){ $this->error('Service yang sama sudah berjalan.'); return self::FAILURE; }
        $run=null;
        try {
            $installation=$active->resolve(); $active->configureClinicDatabase($installation);
            $run=ServiceRun::create(['clinic_id'=>$installation->clinic_id,'service_name'=>'antrean_fktp_add','status'=>'running','started_at'=>now(),'last_heartbeat_at'=>now()]);
            $this->newLine(); $this->info('SERVICE ADD ANTREAN FKTP'); $this->line("Klinik: {$installation->clinic->name}");
            do {
                $run->update(['last_heartbeat_at'=>now()]);
                $hour=(int)now('Asia/Jakarta')->format('Hi');
                if($hour>=400&&$hour<=2300) $service->scan($installation,$run,fn($level,$message)=>$level==='error'?$this->error('['.now()->format('H:i:s')."] {$message}"):$this->line('['.now()->format('H:i:s')."] ".strtoupper($level).' '.$message));
                elseif($this->option('once')) $this->warn('Di luar jam operasional 04:00-23:00.');
                if($this->option('once')) break; sleep(max(1,(int)$this->option('interval')));
            } while(true);
            $run->update(['status'=>'completed','finished_at'=>now(),'message'=>'Service selesai.']); return self::SUCCESS;
        } catch(Throwable $e) { if($run)$run->update(['status'=>'failed','finished_at'=>now(),'message'=>$e->getMessage()]); $this->error($e->getMessage()); return self::FAILURE; }
        finally { $lock->release(); }
    }
}
