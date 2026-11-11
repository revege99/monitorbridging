<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatuSehatConfiguration extends Model
{
    protected $connection = 'clinic';

    protected $table = 'ss_config';

    public $timestamps = false;

    protected $fillable = ['id', 'client_id', 'client_secret', 'organization_id', 'location_id'];

    protected $hidden = ['client_secret'];
}
