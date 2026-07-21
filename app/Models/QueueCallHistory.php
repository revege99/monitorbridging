<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueCallHistory extends Model
{
    protected $connection = 'clinic';
    protected $table = 'riwayat_panggilan_poli';
    public $timestamps = false;

    protected $fillable = [
        'no_rawat', 'no_antrean', 'nama_pasien', 'nama_dokter', 'nama_poli',
        'sumber_panggilan', 'waktu_panggil',
    ];

    protected function casts(): array
    {
        return ['waktu_panggil' => 'datetime'];
    }
}
