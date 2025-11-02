<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MortageController;

Route::post('/mortage/calculate', [MortageController::class, 'calculate']);
