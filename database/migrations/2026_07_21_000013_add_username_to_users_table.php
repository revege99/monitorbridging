<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('users', function (Blueprint $table) {
            $table->string('username', 60)->nullable()->unique()->after('name');
        });

        DB::connection('central')->table('users')->orderBy('id')->get()->each(function ($user) {
            $base = Str::lower(Str::slug(Str::before($user->email, '@'), '_')) ?: 'user';
            $username = $base;
            $number = 1;
            while (DB::connection('central')->table('users')->where('username', $username)->exists()) {
                $username = $base.'_'.++$number;
            }
            DB::connection('central')->table('users')->where('id', $user->id)->update(['username' => $username]);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
