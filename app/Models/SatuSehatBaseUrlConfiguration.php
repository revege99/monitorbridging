<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatuSehatBaseUrlConfiguration extends Model
{
    protected $connection = 'central';
    protected $fillable = ['auth_url', 'fhir_url'];
}
