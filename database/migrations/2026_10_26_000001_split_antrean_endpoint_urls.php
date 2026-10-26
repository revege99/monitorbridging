<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->table('base_url_configurations', function (Blueprint $table) {
            $table->text('base_url_antrean_panggil')->nullable();
        });

        // Convert legacy base URLs once; services use configured URLs verbatim.
        foreach (DB::connection('central')->table('base_url_configurations')->get() as $configuration) {
            $url = rtrim(trim($configuration->base_url_antrean), '/');
            if (preg_match('~/antreanfktp(?:_dev)?/antrean/(add|panggil)$~i', $url)) {
                $root = preg_replace('~/antrean/(add|panggil)$~i', '', $url);
            } elseif (preg_match('~/antreanfktp(?:_dev)?$~i', $url)) {
                $root = $url;
            } else {
                $root = $url.'/antreanfktp';
            }
            DB::connection('central')->table('base_url_configurations')->where('id', $configuration->id)->update([
                'base_url_antrean' => $root.'/antrean/add',
                'base_url_antrean_panggil' => $root.'/antrean/panggil',
            ]);
        }
    }

    public function down(): void
    {
        Schema::connection('central')->table('base_url_configurations', function (Blueprint $table) {
            $table->dropColumn('base_url_antrean_panggil');
        });
    }
};
