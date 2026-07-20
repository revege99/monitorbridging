<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicDatabase extends Model
{
    protected $connection = 'central';
    protected $fillable = ['clinic_id', 'connection_name', 'driver', 'host', 'port', 'database_name', 'username', 'password', 'charset', 'collation'];
    protected $hidden = ['password'];
    protected function casts(): array { return ['password' => 'encrypted']; }
    public function clinic() { return $this->belongsTo(Clinic::class); }
}
