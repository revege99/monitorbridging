<?php

namespace App\Services;

use App\Models\Clinic;

final class ClinicContext
{
    public function __construct(public Clinic $clinic = new Clinic) {}

    public function fingerprint(): string
    {
        $database = $this->clinic->database;

        return hash('sha256', $database->host.'|'.$database->port.'|'.$database->database_name);
    }
}
