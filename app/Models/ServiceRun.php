<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ServiceRun extends Model
{
    protected $connection = 'central';
    protected $fillable = ['service_name','status','started_at','finished_at','last_heartbeat_at','processed','succeeded','failed','message'];
    protected function casts(): array { return ['started_at'=>'datetime','finished_at'=>'datetime','last_heartbeat_at'=>'datetime']; }
    public function logs() { return $this->hasMany(ServiceLog::class); }
}
