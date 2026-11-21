<?php

use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\TarifaController;
use Illuminate\Support\Facades\Route;

// F-04 Registro de pago
Route::get('/pagos', [PagoController::class, 'index']);
Route::get('/pagos/{pago}', [PagoController::class, 'show'])->whereNumber('pago');
Route::post('/pagos', [PagoController::class, 'store']);

// F-08 Administracion de tarifas
Route::get('/tarifas', [TarifaController::class, 'index']);
Route::get('/tarifas/{tarifa}', [TarifaController::class, 'show'])->whereNumber('tarifa');
Route::post('/tarifas', [TarifaController::class, 'store']);
Route::put('/tarifas/{tarifa}', [TarifaController::class, 'update'])->whereNumber('tarifa');
