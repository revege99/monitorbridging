<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PcareConfiguration extends Model
{
    protected $connection = 'clinic';
    protected $table = 'config_pcare';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['id', 'SECRETKEYAPIPCARE', 'CONSIDAPIPCARE', 'USERKEYAPIPCARE', 'PASSPCARE', 'USERPCARE'];
    protected $hidden = ['SECRETKEYAPIPCARE', 'USERKEYAPIPCARE', 'PASSPCARE'];
}
