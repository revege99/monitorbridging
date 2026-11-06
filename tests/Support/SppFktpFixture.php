<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SppFktpFixture
{
    public const NO_RAWAT = '2026/11/02/000001';

    public static function seed(): void
    {
        // Hanya SQLite in-memory; tidak pernah mengakses database klinik nyata.
        config(['database.connections.clinic' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('clinic');

        $columns = [
            'reg_periksa' => ['no_rawat', 'no_rkm_medis', 'tgl_registrasi', 'status_lanjut'],
            'pasien' => ['no_rkm_medis', 'nm_pasien', 'no_ktp', 'jk', 'tgl_lahir', 'no_tlp', 'alamat', 'no_peserta'],
            'pcare_pendaftaran' => ['no_rawat', 'noKartu', 'tglDaftar', 'kdTkp'],
            'pcare_tindakan_ralan_diberikan' => ['no_rawat', 'noKunjungan', 'kd_jenis_prw', 'tgl_perawatan', 'jam'],
            'jns_perawatan' => ['kd_jenis_prw', 'nm_perawatan'],
            'setting' => ['kode_ppk', 'nama_instansi', 'aktifkan'],
        ];
        foreach ($columns as $name => $fields) {
            Schema::connection('clinic')->create($name, function (Blueprint $table) use ($fields) {
                foreach ($fields as $field) {
                    $table->string($field)->nullable();
                }
            });
        }

        $db = DB::connection('clinic');
        $db->table('setting')->insert([
            'kode_ppk' => '00000000', 'nama_instansi' => 'KLINIK CONTOH', 'aktifkan' => 'Yes',
        ]);
        $db->table('reg_periksa')->insert([
            'no_rawat' => self::NO_RAWAT, 'no_rkm_medis' => '000001',
            'tgl_registrasi' => '2026-11-01', 'status_lanjut' => 'Ralan',
        ]);
        $db->table('pasien')->insert([
            'no_rkm_medis' => '000001', 'nm_pasien' => 'PASIEN CONTOH',
            'no_ktp' => '0000000000000000', 'jk' => 'P', 'tgl_lahir' => '1997-07-07',
            'no_tlp' => '080000000000', 'alamat' => 'JL. CONTOH NOMOR 1', 'no_peserta' => '0000000000000',
        ]);
        $db->table('pcare_pendaftaran')->insert([
            'no_rawat' => self::NO_RAWAT, 'noKartu' => '0000000000001',
            'tglDaftar' => '2026-11-02', 'kdTkp' => '10 Rawat Jalan',
        ]);
        $db->table('jns_perawatan')->insert([
            'kd_jenis_prw' => 'ANC', 'nm_perawatan' => 'Pelayanan ANC',
        ]);
        $db->table('pcare_tindakan_ralan_diberikan')->insert([
            'no_rawat' => self::NO_RAWAT, 'noKunjungan' => '000000001126P000001',
            'kd_jenis_prw' => 'ANC', 'tgl_perawatan' => '2026-11-02', 'jam' => '10:00:00',
        ]);
    }
}
