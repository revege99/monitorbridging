<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseUrlConfiguration extends Model
{
    protected $connection = 'central';
    protected $fillable = ['base_url_pcare', 'base_url_antrean'];
}
