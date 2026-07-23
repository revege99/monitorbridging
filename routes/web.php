<?php

use App\Http\Controllers\BridgingPelayananController;
use App\Http\Controllers\BpjsTidakBridgingController;
use App\Http\Controllers\KendalaBridgingController;
use App\Http\Controllers\SatuSehatEncounterController;
use App\Http\Controllers\SatuSehatConditionController;
use App\Http\Controllers\SatuSehatClinicalImpressionController;
use App\Http\Controllers\SatuSehatMedicationRequestController;
use App\Http\Controllers\SatuSehatMedicationDispenseController;
use App\Http\Controllers\SatuSehatMedicationStatementController;
use App\Http\Controllers\SatuSehatCarePlanController;
use App\Http\Controllers\SatuSehatObservationController;
use App\Http\Controllers\SatuSehatProcedureController;
use App\Http\Controllers\StatistikPasienController;
use App\Http\Controllers\TimelinePelayananController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SetupClinicController;
use App\Http\Controllers\BpjsConfigurationController;
use App\Http\Controllers\SatuSehatConfigurationController;
use App\Http\Controllers\BaseUrlConfigurationController;
use App\Http\Controllers\SatuSehatBaseUrlConfigurationController;
use App\Http\Controllers\InstallationController;
use App\Http\Controllers\QueueDisplayController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\ConfigureClinicDatabase;
use Illuminate\Support\Facades\Route;

Route::get('/display/antrean', [QueueDisplayController::class, 'index'])->name('display.queue');
Route::get('/anjungan', [KioskController::class, 'index'])->name('kiosk.index');
Route::post('/anjungan/cek-peserta', [KioskController::class, 'lookup'])->name('kiosk.lookup');
Route::post('/anjungan/pasien', [KioskController::class, 'createPatient'])->name('kiosk.patient.create');
Route::post('/anjungan/antrean', [KioskController::class, 'register'])->name('kiosk.register');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', ConfigureClinicDatabase::class])->group(function () {
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/akun/password', [AuthController::class, 'updatePassword'])->name('account.password.update');
Route::post('/klinik/aktif', [SetupClinicController::class, 'selectClinic'])->name('clinics.select');
Route::get('/setup-klinik', [SetupClinicController::class, 'index'])->name('setup.index');
Route::post('/setup-klinik/profil', [SetupClinicController::class, 'storeClinic'])->name('setup.clinics.store');
Route::put('/setup-klinik/profil/{clinic}', [SetupClinicController::class, 'updateClinic'])->name('setup.clinics.update');
Route::delete('/setup-klinik/profil/{clinic}', [SetupClinicController::class, 'deleteClinic'])->name('setup.clinics.destroy');
Route::post('/setup-klinik/database', [SetupClinicController::class, 'saveDatabase'])->name('setup.database.save');
Route::post('/setup-klinik/database/test', [SetupClinicController::class, 'testDatabase'])->name('setup.database.test');
Route::post('/setup-klinik/users', [SetupClinicController::class, 'storeUser'])->name('setup.users.store');
Route::put('/setup-klinik/users/{user}', [SetupClinicController::class, 'updateUser'])->name('setup.users.update');
Route::delete('/setup-klinik/users/{user}', [SetupClinicController::class, 'deleteUser'])->name('setup.users.destroy');
Route::get('/konfigurasi-bridging/bpjs', [BpjsConfigurationController::class, 'index'])->name('configurations.bpjs.index');
Route::post('/konfigurasi-bridging/bpjs', [BpjsConfigurationController::class, 'store'])->name('configurations.bpjs.store');
Route::put('/konfigurasi-bridging/bpjs/{bpjsConfiguration}', [BpjsConfigurationController::class, 'update'])->name('configurations.bpjs.update');
Route::delete('/konfigurasi-bridging/bpjs/{bpjsConfiguration}', [BpjsConfigurationController::class, 'destroy'])->name('configurations.bpjs.destroy');
Route::get('/konfigurasi-bridging/satu-sehat', [SatuSehatConfigurationController::class, 'index'])->name('configurations.satu-sehat.index');
Route::post('/konfigurasi-bridging/satu-sehat', [SatuSehatConfigurationController::class, 'store'])->name('configurations.satu-sehat.store');
Route::put('/konfigurasi-bridging/satu-sehat/{satuSehatConfiguration}', [SatuSehatConfigurationController::class, 'update'])->name('configurations.satu-sehat.update');
Route::delete('/konfigurasi-bridging/satu-sehat/{satuSehatConfiguration}', [SatuSehatConfigurationController::class, 'destroy'])->name('configurations.satu-sehat.destroy');
Route::get('/konfigurasi-bridging/base-url', [BaseUrlConfigurationController::class, 'index'])->name('configurations.base-url.index');
Route::post('/konfigurasi-bridging/base-url', [BaseUrlConfigurationController::class, 'store'])->name('configurations.base-url.store');
Route::put('/konfigurasi-bridging/base-url/{baseUrlConfiguration}', [BaseUrlConfigurationController::class, 'update'])->name('configurations.base-url.update');
Route::delete('/konfigurasi-bridging/base-url/{baseUrlConfiguration}', [BaseUrlConfigurationController::class, 'destroy'])->name('configurations.base-url.destroy');
Route::get('/konfigurasi-bridging/base-url-satu-sehat', [SatuSehatBaseUrlConfigurationController::class, 'index'])->name('configurations.satu-sehat-base-url.index');
Route::post('/konfigurasi-bridging/base-url-satu-sehat', [SatuSehatBaseUrlConfigurationController::class, 'store'])->name('configurations.satu-sehat-base-url.store');
Route::put('/konfigurasi-bridging/base-url-satu-sehat/{satuSehatBaseUrlConfiguration}', [SatuSehatBaseUrlConfigurationController::class, 'update'])->name('configurations.satu-sehat-base-url.update');
Route::delete('/konfigurasi-bridging/base-url-satu-sehat/{satuSehatBaseUrlConfiguration}', [SatuSehatBaseUrlConfigurationController::class, 'destroy'])->name('configurations.satu-sehat-base-url.destroy');
Route::get('/aktivasi-instalasi', [InstallationController::class, 'index'])->name('installation.index');
Route::post('/aktivasi-instalasi', [InstallationController::class, 'activate'])->name('installation.activate');
Route::get('/service-monitor/antrean', [InstallationController::class, 'monitor'])->name('service-monitor.index');
Route::get('/service-monitor/antrean/logs', [InstallationController::class, 'logs'])->name('service-monitor.logs');

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/monitoring/bridging-pelayanan', [BridgingPelayananController::class, 'index'])->name('monitoring.bridging-pelayanan');
Route::get('/monitoring/statistik-pasien', [StatistikPasienController::class, 'index'])->name('monitoring.statistik-pasien');
Route::get('/monitoring/bpjs-tidak-bridging', [BpjsTidakBridgingController::class, 'index'])->name('monitoring.bpjs-tidak-bridging');
Route::get('/monitoring/kendala-bridging', [KendalaBridgingController::class, 'index'])->name('monitoring.kendala-bridging');
Route::post('/monitoring/kendala-bridging/perbaiki', [KendalaBridgingController::class, 'perbaiki'])->name('monitoring.kendala-bridging.perbaiki');
Route::get('/monitoring/pasien-rujuk', function () {
    return redirect('/?jenis=rujuk');
})->name('monitoring.pasien-rujuk');
Route::get('/monitoring/timeline-pelayanan', [TimelinePelayananController::class, 'index'])->name('monitoring.timeline-pelayanan');
Route::get('/satu-sehat/encounter', [SatuSehatEncounterController::class, 'index'])->name('satu-sehat.encounter');
Route::get('/satu-sehat/condition', [SatuSehatConditionController::class, 'index'])->name('satu-sehat.condition');
Route::get('/satu-sehat/observation', [SatuSehatObservationController::class, 'index'])->name('satu-sehat.observation');
Route::get('/satu-sehat/procedure', [SatuSehatProcedureController::class, 'index'])->name('satu-sehat.procedure');
Route::get('/satu-sehat/clinical-impression', [SatuSehatClinicalImpressionController::class, 'index'])->name('satu-sehat.clinical-impression');
Route::get('/satu-sehat/medication-request', [SatuSehatMedicationRequestController::class, 'index'])->name('satu-sehat.medication-request');
Route::get('/satu-sehat/medication-dispense', [SatuSehatMedicationDispenseController::class, 'index'])->name('satu-sehat.medication-dispense');
Route::get('/satu-sehat/medication-statement', [SatuSehatMedicationStatementController::class, 'index'])->name('satu-sehat.medication-statement');
Route::get('/satu-sehat/care-plan', [SatuSehatCarePlanController::class, 'index'])->name('satu-sehat.care-plan');
});
