<?php

use App\Http\Controllers\Api\PagoController;
use Illuminate\Support\Facades\Route;

// F-04 Registro de pago
Route::get('/pagos', [PagoController::class, 'index']);
Route::get('/pagos/{pago}', [PagoController::class, 'show'])->whereNumber('pago');
Route::post('/pagos', [PagoController::class, 'store']);
