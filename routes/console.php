<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Clinic;
use App\Models\ClinicDatabase;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:install', function () {
    $config = config('database.connections.central');
    $database = preg_replace('/[^a-zA-Z0-9_]/', '', $config['database']);
    $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $this->call('migrate', ['--database' => 'central', '--force' => true]);
    $clinic = Clinic::firstOrCreate(['code' => 'KLINIK-001'], [
        'name' => env('APP_NAME', 'Klinik Utama'), 'is_active' => true,
    ]);
    ClinicDatabase::firstOrCreate(['clinic_id' => $clinic->id], [
        'connection_name' => 'SIMRS Utama', 'driver' => 'mariadb',
        'host' => env('DB_HOST', '127.0.0.1'), 'port' => env('DB_PORT', 3306),
        'database_name' => env('DB_DATABASE'), 'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''), 'charset' => env('DB_CHARSET', 'utf8mb4'),
        'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
    ]);
    User::firstOrCreate(['email' => 'superadmin@localhost'], [
        'name' => 'Super Administrator', 'password' => Hash::make('Admin@12345'),
        'role' => 'superadmin', 'is_active' => true,
    ]);
    $this->info('Database pusat dan akun superadmin siap.');
})->purpose('Create the central application database and initial superadmin');
