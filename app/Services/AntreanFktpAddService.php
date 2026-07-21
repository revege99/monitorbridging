<?php
namespace App\Services;
use App\Models\BaseUrlConfiguration;
use App\Models\InstallationSetting;
use App\Models\ServiceLog;
use App\Models\ServiceRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;
class AntreanFktpAddService
{
    public function scan(InstallationSetting $installation, ServiceRun $run, callable $output): void
    {
        $now = now('Asia/Jakarta'); $tanggal=$now->toDateString(); $hari=['Monday'=>'SENIN','Tuesday'=>'SELASA','Wednesday'=>'RABU','Thursday'=>'KAMIS','Friday'=>'JUMAT','Saturday'=>'SABTU','Sunday'=>'MINGGU'][$now->englishDayOfWeek];
        $this->log($installation,$run,$output,'info','scan',"Cek pasien BPJS tanggal {$tanggal} ({$hari}) ...");
        $rows = DB::connection('clinic')->select($this->query(), [$hari,$tanggal]);
        if (!$rows) { $this->log($installation,$run,$output,'info','idle','Tidak ada pasien BPJS baru hari ini.'); return; }
        foreach ($rows as $row) {
            $data=(array)$row; $run->increment('processed');
            try { $this->send($installation,$run,$data,$tanggal,$output); }
            catch (Throwable $e) { $run->increment('failed'); $this->log($installation,$run,$output,'error','failed',"{$data['no_rawat']}: {$e->getMessage()}",$data['no_rawat']); }
            sleep(1);
        }
    }
    private function send(InstallationSetting $installation, ServiceRun $run, array $data, string $tanggal, callable $output): void
    {
        [$mulai,$selesai]=array_pad(explode('-',$data['jam_praktek'] ?? ''),2,'');
        $payload=['nomorkartu'=>$data['no_peserta'],'nik'=>$data['no_ktp'],'nohp'=>$data['nohp'],'kodepoli'=>$data['kd_poli'],'namapoli'=>$data['nm_poli'],'norm'=>$data['no_rkm_medis'],'tanggalperiksa'=>$tanggal,'kodedokter'=>$data['kd_dokter_pcare'],'namadokter'=>$data['nm_dokter'],'jampraktek'=>date('H:i',strtotime($mulai)).'-'.date('H:i',strtotime($selesai)),'nomorantrean'=>$data['nomor'],'angkaantrean'=>(int)$data['angka'],'keterangan'=>''];
        $bpjs=$installation->clinic->bpjsConfiguration; $base=BaseUrlConfiguration::firstOrFail();
        $timestamp=time(); $signature=base64_encode(hash_hmac('sha256',$bpjs->cons_id.'&'.$timestamp,$bpjs->secret_key,true));
        $url=$this->addQueueUrl($base->base_url_antrean);
        $this->log($installation,$run,$output,'info','sending',"Mengirim {$data['no_rawat']} ({$data['nm_pasien']})",$data['no_rawat']); $started=microtime(true);
        $response=Http::timeout(60)->withHeaders(['X-cons-id'=>$bpjs->cons_id,'X-timestamp'=>(string)$timestamp,'X-signature'=>$signature,'user_key'=>$bpjs->user_key_antrol,'X-authorization'=>'Basic '.base64_encode($bpjs->username.':'.$bpjs->password.':'.$bpjs->kode_aplikasi)])->post($url,$payload);
        $json=$response->json(); $code=(int)data_get($json,'metadata.code',$response->status());
        $message=trim((string)data_get($json,'metadata.message',''));
        if ($message === '') {
            $body = trim(strip_tags($response->body()));
            $message = 'HTTP '.$response->status().($body !== '' ? ': '.mb_strimwidth(preg_replace('/\s+/', ' ', $body), 0, 180, '...') : ' tanpa pesan');
        }
        $terminal=$this->terminal($code,$message);
        $this->saveResult($data,$response->body(),$code,$message);
        $run->increment($terminal?'succeeded':'failed');
        $this->log($installation,$run,$output,$terminal?'success':'warning',$terminal?'success':'retry',"{$data['no_rawat']} => {$code} | {$message}",$data['no_rawat'],$code,(int)((microtime(true)-$started)*1000),['payload'=>$payload]);
    }
    private function terminal(int $code,string $message): bool
    {
        $m=preg_replace('/\s+/',' ',strtolower(trim($message))); return ($code===200&&$m==='ok')||($code===201&&$m==='peserta sudah terdaftar di poli tersebut pada hari ini');
    }
    private function addQueueUrl(string $baseUrl): string
    {
        $url = rtrim(trim($baseUrl), '/');
        if (preg_match('~/antreanfktp(?:_dev)?/antrean/add$~i', $url)) return $url;
        if (preg_match('~/antreanfktp(?:_dev)?/antrean/panggil$~i', $url)) {
            return preg_replace('~/panggil$~i', '/add', $url);
        }
        if (preg_match('~/antreanfktp(?:_dev)?$~i', $url)) return $url.'/antrean/add';
        return $url.'/antreanfktp/antrean/add';
    }
    private function saveResult(array $data,string $response,int $code,string $message): void
    {
        $values=['tgl_kirim'=>now(),'response'=>$response,'status_code'=>(string)$code,'message'=>$message];
        if (!empty($data['antrean_bpjs_id'])) DB::connection('clinic')->table('antrean_terkirim_bpjs')->where('id',$data['antrean_bpjs_id'])->update($values);
        else DB::connection('clinic')->table('antrean_terkirim_bpjs')->insert($values+['no_rawat'=>$data['no_rawat']]);
    }
    private function log(InstallationSetting $i,ServiceRun $r,callable $out,string $level,string $status,string $message,?string $reference=null,?int $code=null,?int $duration=null,array $context=[]): void
    {
        ServiceLog::create(['service_run_id'=>$r->id,'clinic_id'=>$i->clinic_id,'service_name'=>'antrean_fktp_add','level'=>$level,'status'=>$status,'reference'=>$reference,'message'=>$message,'response_code'=>$code,'duration_ms'=>$duration,'context'=>$context?:null]); $out($level,$message);
    }
    private function query(): string
    {
        return <<<'SQL'
SELECT DISTINCT rp.no_rawat,rp.no_reg AS nomor,RIGHT(rp.no_reg,3) AS angka,ps.nm_pasien,ps.no_tlp AS nohp,ps.no_rkm_medis,ps.no_peserta,ps.no_ktp,mpp.kd_poli_pcare AS kd_poli,pk.nm_poli,mdk.kd_dokter_pcare,d.nm_dokter,CONCAT(j.jam_mulai,'-',j.jam_selesai) AS jam_praktek,atb.id AS antrean_bpjs_id,atb.status_code AS last_status_code,atb.message AS last_message
FROM reg_periksa rp INNER JOIN poliklinik pk ON rp.kd_poli=pk.kd_poli INNER JOIN pasien ps ON rp.no_rkm_medis=ps.no_rkm_medis INNER JOIN dokter d ON rp.kd_dokter=d.kd_dokter INNER JOIN (SELECT kd_dokter,hari_kerja,MIN(jam_mulai) AS jam_mulai,MAX(jam_selesai) AS jam_selesai FROM jadwal GROUP BY kd_dokter,hari_kerja) j ON rp.kd_dokter=j.kd_dokter AND j.hari_kerja=? INNER JOIN maping_dokter_pcare mdk ON d.kd_dokter=mdk.kd_dokter INNER JOIN maping_poliklinik_pcare mpp ON rp.kd_poli=mpp.kd_poli_rs
LEFT JOIN (SELECT t1.id,t1.no_rawat,t1.status_code,t1.message FROM antrean_terkirim_bpjs t1 INNER JOIN (SELECT no_rawat,MAX(id) max_id FROM antrean_terkirim_bpjs GROUP BY no_rawat)t2 ON t1.id=t2.max_id)atb ON rp.no_rawat=atb.no_rawat
WHERE mpp.kd_poli_pcare IN ('001','U0010','U0035','003','999') AND rp.kd_pj='bpj' AND rp.tgl_registrasi=? AND (atb.id IS NULL OR NOT ((COALESCE(atb.status_code,0)=200 AND LOWER(TRIM(COALESCE(atb.message,'')))='ok') OR (COALESCE(atb.status_code,0)=201 AND LOWER(TRIM(COALESCE(atb.message,'')))='peserta sudah terdaftar di poli tersebut pada hari ini')))
SQL;
    }
}
