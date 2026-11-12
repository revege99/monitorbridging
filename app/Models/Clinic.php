<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    protected $connection = 'central';
    protected $table = 'application_profiles';
    protected $fillable = ['id', 'code', 'name', 'legal_name', 'phone', 'email', 'address', 'city', 'province', 'postal_code', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function database() { return $this->hasOne(ClinicDatabase::class, 'id', 'id'); }
    public function bpjsConfiguration() { return $this->hasOne(BpjsConfiguration::class, 'id', 'id'); }
}
