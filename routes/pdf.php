<?php

use App\Http\Controllers\Api\Pdf\Bpjs\SppFktpController;
use App\Http\Middleware\AuthenticatePdfIntegration;
use Illuminate\Support\Facades\Route;

// Semua route di sini mendapat prefix /api/integration/pdf, autentikasi, dan throttle.
Route::prefix('bpjs')->name('bpjs.')->group(function () {
    Route::get('sppFKTP', SppFktpController::class)
        ->withoutMiddleware(AuthenticatePdfIntegration::class)
        ->name('sppFKTP');
    Route::post('sppFKTP', SppFktpController::class)->name('sppFKTP.post');
});
