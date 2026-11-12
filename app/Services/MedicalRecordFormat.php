<?php

namespace App\Services;

use App\Models\KioskConfiguration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MedicalRecordFormat
{
    public function __construct(public readonly KioskConfiguration $configuration) {}

    public function format(int $number): string
    {
        if ($number < 1 || $number > 10 ** $this->configuration->rm_digits - 1) {
            throw new RuntimeException('Nomor RM melebihi kapasitas format. Tambahkan jumlah digit di Konfigurasi Anjungan.');
        }
        $digits = str_pad((string) $number, $this->configuration->rm_digits, '0', STR_PAD_LEFT);
        if (! $this->configuration->rm_use_separator) {
            return $digits;
        }
        $parts = [];
        $offset = 0;
        foreach (explode('-', $this->configuration->rm_groups) as $size) {
            $parts[] = substr($digits, $offset, (int) $size);
            $offset += (int) $size;
        }

        return implode($this->configuration->rm_separator, $parts);
    }

    public function pattern(): string
    {
        if (! $this->configuration->rm_use_separator) {
            return '[0-9]{'.$this->configuration->rm_digits.'}';
        }
        $separator = '['.str_replace('/', '\\/', preg_quote($this->configuration->rm_separator, '~')).']';

        return implode($separator, array_map(fn ($size) => '[0-9]{'.$size.'}', explode('-', $this->configuration->rm_groups)));
    }

    public function clientSettings(): array
    {
        return ['pattern' => $this->pattern(), 'maxlength' => strlen($this->format(1)), 'example' => $this->format(1)];
    }

    private function normalizedSql(): string
    {
        return "REPLACE(REPLACE(REPLACE(no_rkm_medis, '-', ''), '.', ''), '/', '')";
    }

    private function numericPatients(): Builder
    {
        $expression = $this->normalizedSql();
        $query = DB::connection('clinic')->table('pasien')->whereRaw("LENGTH({$expression}) BETWEEN 1 AND 15");
        if (DB::connection('clinic')->getDriverName() === 'sqlite') {
            return $query->whereRaw("{$expression} NOT GLOB '*[^0-9]*'");
        }

        return $query->whereRaw("{$expression} REGEXP '^[0-9]+$'");
    }

    private function numericSql(): string
    {
        $type = DB::connection('clinic')->getDriverName() === 'sqlite' ? 'INTEGER' : 'UNSIGNED';

        return 'CAST('.$this->normalizedSql().' AS '.$type.')';
    }

    public function next(): string
    {
        // Angka terbesar + 1 dari pasien, tanpa prefix tetap atau acuan set_no_rkm_medis.
        // Pemisah dan nol di depan hanya format tampilan, bukan pembatas urutan.
        $last = $this->numericPatients()->selectRaw('MAX('.$this->numericSql().') AS last_number')->value('last_number');

        return $this->format(((int) $last) + 1);
    }

    public function existsEquivalent(string $medicalRecord): bool
    {
        $number = (int) str_replace(['-', '.', '/'], '', $medicalRecord);

        return $this->numericPatients()->whereRaw($this->numericSql().' = ?', [$number])->lockForUpdate()->first() !== null;
    }
}
