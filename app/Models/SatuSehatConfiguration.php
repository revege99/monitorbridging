<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatuSehatConfiguration extends Model
{
    protected $connection = 'central';
    protected $fillable = ['clinic_id', 'client_id', 'client_secret', 'organization_id', 'location_id'];
    protected $hidden = ['client_secret'];
    protected function casts(): array { return ['client_secret' => 'encrypted']; }
    public function clinic() { return $this->belongsTo(Clinic::class); }
}
