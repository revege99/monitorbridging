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
use App\Http\Middleware\ConfigureClinicDatabase;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', ConfigureClinicDatabase::class])->group(function () {
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
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

Route::get('/', [BridgingPelayananController::class, 'index']);
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
