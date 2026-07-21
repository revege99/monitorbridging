<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ServiceLog extends Model
{
    protected $connection = 'central';
    protected $fillable = ['service_run_id','clinic_id','service_name','level','status','reference','message','response_code','duration_ms','context'];
    protected function casts(): array { return ['context'=>'array']; }
}
