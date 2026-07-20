<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    protected $connection = 'central';
    protected $fillable = ['code', 'name', 'legal_name', 'phone', 'email', 'address', 'city', 'province', 'postal_code', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function database() { return $this->hasOne(ClinicDatabase::class); }
    public function users() { return $this->hasMany(User::class); }
}
