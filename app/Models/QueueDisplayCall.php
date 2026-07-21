<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueDisplayCall extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'clinic_id', 'queue_number', 'patient_name', 'doctor_name', 'clinic_name', 'called_at',
    ];

    protected function casts(): array
    {
        return ['called_at' => 'datetime'];
    }
}
