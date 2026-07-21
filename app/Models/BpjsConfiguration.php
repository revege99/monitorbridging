<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BpjsConfiguration extends Model
{
    protected $connection = 'central';
    protected $fillable = ['clinic_id', 'cons_id', 'secret_key', 'user_key_antrol', 'user_key_pcare', 'kode_aplikasi', 'username', 'password'];
    protected $hidden = ['secret_key', 'user_key_antrol', 'user_key_pcare', 'password'];

    protected function casts(): array
    {
        return [
            'secret_key' => 'encrypted',
            'user_key_antrol' => 'encrypted',
            'user_key_pcare' => 'encrypted',
            'password' => 'encrypted',
        ];
    }

    public function clinic() { return $this->belongsTo(Clinic::class); }
}
