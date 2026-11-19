<?php

use App\Http\Controllers\Api\Pdf\Bpjs\SppFktpController;
use App\Http\Controllers\Api\Pdf\Bpjs\RujukSubspesialisController;
use App\Http\Controllers\Api\Pdf\HealthCertificateController;
use App\Http\Controllers\Api\Pdf\SickCertificateController;
use App\Http\Middleware\AuthenticatePdfIntegration;
use Illuminate\Support\Facades\Route;

// Semua route di sini mendapat prefix /api/integration/pdf, autentikasi, dan throttle.
Route::prefix('bpjs')->name('bpjs.')->group(function () {
    Route::get('sppFKTP', SppFktpController::class)
        ->withoutMiddleware(AuthenticatePdfIntegration::class)
        ->name('sppFKTP');
    Route::post('sppFKTP', SppFktpController::class)->name('sppFKTP.post');
    Route::get('rujukSubspesialis', RujukSubspesialisController::class)
        ->withoutMiddleware(AuthenticatePdfIntegration::class)
        ->name('rujukSubspesialis');
    Route::post('rujukSubspesialis', RujukSubspesialisController::class)->name('rujukSubspesialis.post');
    Route::get('suratKeteranganSehat', HealthCertificateController::class)->withoutMiddleware(AuthenticatePdfIntegration::class)->name('suratKeteranganSehat');
    Route::post('suratKeteranganSehat', HealthCertificateController::class)->name('suratKeteranganSehat.post');
    Route::get('suratKeteranganSakit', SickCertificateController::class)->withoutMiddleware(AuthenticatePdfIntegration::class)->name('suratKeteranganSakit');
    Route::post('suratKeteranganSakit', SickCertificateController::class)->name('suratKeteranganSakit.post');
});
