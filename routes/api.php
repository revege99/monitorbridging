<?php

use App\Http\Controllers\Api\AntreanFktpCallController;
use App\Http\Controllers\QueueDisplayController;
use Illuminate\Support\Facades\Route;

Route::post('/integration/antrean-fktp/panggil', AntreanFktpCallController::class)
    ->defaults('queue_status', 1)
    ->middleware('throttle:120,1')
    ->name('api.integration.antrean-fktp.panggil');

Route::post('/integration/antrean-fktp/tidak-hadir', AntreanFktpCallController::class)
    ->defaults('queue_status', 2)
    ->middleware('throttle:120,1')
    ->name('api.integration.antrean-fktp.tidak-hadir');

Route::post('/integration/display/panggil', [QueueDisplayController::class, 'store'])
    ->middleware('throttle:120,1')
    ->name('api.integration.display.store');

Route::get('/display/antrean/state', [QueueDisplayController::class, 'state'])
    ->middleware('throttle:180,1')
    ->name('api.display.state');
