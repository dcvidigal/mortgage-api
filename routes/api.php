<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MortageController;

Route::middleware(\App\Http\Middleware\ApiTokenAuth::class)->group(function () {
    Route::post('/mortage/calculate', [MortageController::class, 'calculate']);
    Route::post('/mortage/amortization-schedule', [MortageController::class, 'amortizationSchedule']);
    Route::post('/mortage/calculate-spread', [MortageController::class, 'calculateWithSpread']);
    Route::post('/mortage/export', [MortageController::class, 'export']);
});
