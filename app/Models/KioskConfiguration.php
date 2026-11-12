<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KioskConfiguration extends Model
{
    protected $connection = 'central';

    protected $fillable = ['id', 'rm_digits', 'rm_use_separator', 'rm_separator', 'rm_groups'];

    protected $attributes = ['rm_digits' => 6, 'rm_use_separator' => true, 'rm_separator' => '-', 'rm_groups' => '2-2-2'];

    protected function casts(): array
    {
        return ['rm_digits' => 'integer', 'rm_use_separator' => 'boolean'];
    }

    public static function current(): self
    {
        return static::find(1) ?? new static;
    }
}
