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
        $next = $this->counterNumber($this->counter()) + 1;
        // Counter lama dapat tertinggal; lewati nomor terpakai tanpa mengambil MAX seluruh pasien.
        do {
            $this->format($next);
            $rows = $this->numericPatients()->whereRaw($this->numericSql().' >= ?', [$next])
                ->selectRaw($this->numericSql().' AS number')->orderByRaw($this->numericSql().' ASC')->limit(500)->get();
            foreach ($rows as $row) {
                $used = (int) $row->number;
                if ($used > $next) {
                    return $this->format($next);
                }
                if ($used === $next) {
                    $next++;
                }
            }
        } while ($rows->count() === 500);

        return $this->format($next);
    }

    private function counter(bool $lock = false): string
    {
        $query = DB::connection('clinic')->table('set_no_rkm_medis');
        if ($lock) {
            $query->lockForUpdate();
        }
        $rows = $query->get(['no_rkm_medis']);
        if ($rows->count() !== 1) {
            throw new RuntimeException('Tabel set_no_rkm_medis harus memiliki tepat satu baris nomor RM terakhir.');
        }
        $value = (string) $rows->first()->no_rkm_medis;
        $this->counterNumber($value);

        return $value;
    }

    private function counterNumber(string $value): int
    {
        $value = str_replace(['-', '.', '/'], '', $value);
        if (! preg_match('/^[0-9]{1,15}$/D', $value)) {
            throw new RuntimeException('Nomor terakhir pada set_no_rkm_medis tidak valid.');
        }

        return (int) $value;
    }

    public function lockCounterForSave(string $medicalRecord): string
    {
        if (DB::connection('clinic')->transactionLevel() < 1) {
            throw new \LogicException('Pencatat RM harus dikunci di dalam transaksi pasien.');
        }
        $previous = $this->counter(true);
        $this->format($this->counterNumber($medicalRecord));
        if ($this->counterNumber($medicalRecord) <= $this->counterNumber($previous)) {
            throw new RuntimeException('Nomor RM terakhir sudah berubah. Periksa ulang peserta untuk memperoleh nomor terbaru.');
        }

        return $previous;
    }

    public function advanceAfterSave(string $medicalRecord, string $previous): void
    {
        $db = DB::connection('clinic');
        if ($db->transactionLevel() < 1 || ! $db->table('pasien')->where('no_rkm_medis', $medicalRecord)->exists()) {
            throw new \LogicException('Pencatat RM hanya diperbarui sesudah pasien disimpan dalam transaksi yang sama.');
        }
        $number = $this->counterNumber($medicalRecord);
        if ($number <= $this->counterNumber($previous)) {
            throw new RuntimeException('Nomor pencatat RM tidak boleh mundur.');
        }
        // Counter tetap numerik tanpa pemisah, sesuai pencatat urutan SIMRS.
        $counter = str_pad((string) $number, $this->configuration->rm_digits, '0', STR_PAD_LEFT);
        $updated = $db->table('set_no_rkm_medis')->where('no_rkm_medis', $previous)->update(['no_rkm_medis' => $counter]);
        if ($updated !== 1) {
            throw new RuntimeException('Pencatat RM berubah saat penyimpanan. Silakan ulangi pendaftaran.');
        }
    }

    public function existsEquivalent(string $medicalRecord): bool
    {
        $number = (int) str_replace(['-', '.', '/'], '', $medicalRecord);

        return $this->numericPatients()->whereRaw($this->numericSql().' = ?', [$number])->lockForUpdate()->first() !== null;
    }
}
