<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\CobroController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\TarifaController;
use Illuminate\Support\Facades\Route;

// F-03 / F-09 Cobros (consulta: ambos roles; anular: administrador)
Route::get('/cobros', [CobroController::class, 'index']);
Route::get('/cobros/{cobro}', [CobroController::class, 'show'])->whereNumber('cobro');
Route::patch('/cobros/{cobro}/anular', [CobroController::class, 'anular'])->whereNumber('cobro');

// F-04 Registro de pago en efectivo (operador)
Route::post('/cobros/{cobro}/pagos', [PagoController::class, 'store'])->whereNumber('cobro');

// F-08 Administracion de tarifas (lectura: ambos roles; escritura: administrador)
Route::get('/tarifas', [TarifaController::class, 'index']);
Route::post('/tarifas', [TarifaController::class, 'store']);
Route::put('/tarifas/{tarifa}', [TarifaController::class, 'update'])->whereNumber('tarifa');

// Acceso temporal abierto mientras se define la autenticación de la API.
Route::prefix('dashboard')->group(function (): void {
    Route::get('/sedes', [DashboardController::class, 'sedes']);
    Route::get('/resumen', [DashboardController::class, 'resumen']);
    Route::get('/actividad', [DashboardController::class, 'actividad']);
    Route::get('/comparativo-sedes', [DashboardController::class, 'comparativo']);
});
