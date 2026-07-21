<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InstallationSetting extends Model
{
    protected $connection = 'central';
    protected $fillable = ['clinic_id','installation_uuid','is_activated','activated_at','activated_by'];
    protected function casts(): array { return ['is_activated'=>'boolean','activated_at'=>'datetime']; }
    public function clinic() { return $this->belongsTo(Clinic::class); }
    public function activator() { return $this->belongsTo(User::class, 'activated_by'); }
}
