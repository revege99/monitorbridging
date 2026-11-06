<?php

use App\Http\Controllers\Api\Pdf\Bpjs\SppFktpController;
use Illuminate\Support\Facades\Route;

// Semua route di sini mendapat prefix /api/integration/pdf, autentikasi, dan throttle.
Route::prefix('bpjs')->name('bpjs.')->group(function () {
    Route::match(['get', 'post'], 'sppFKTP', SppFktpController::class)->name('sppFKTP');
});
