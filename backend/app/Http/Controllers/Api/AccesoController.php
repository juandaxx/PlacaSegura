<?php

namespace App\Http\Controllers\Api;

use App\Application\Services\ServicioAcceso;
use App\Http\Controllers\Api\Concerns\RespondeResultadoAcceso;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarIngresoRequest;
use App\Http\Requests\RegistrarSalidaRequest;
use Illuminate\Http\JsonResponse;

/**
 * Carril de entrada y salida (F-01, F-02, F-03, F-05, F-07). Solo traduce
 * HTTP: las reglas viven en ServicioAcceso.
 */
class AccesoController extends Controller
{
    use RespondeResultadoAcceso;

    /**
     * POST /api/accesos/ingreso (operador)
     */
    public function ingreso(RegistrarIngresoRequest $request, ServicioAcceso $acceso): JsonResponse
    {
        return $this->responderAcceso(fn () => $acceso->registrarIngreso($request->lectura()));
    }

    /**
     * POST /api/accesos/salida (operador)
     */
    public function salida(RegistrarSalidaRequest $request, ServicioAcceso $acceso): JsonResponse
    {
        return $this->responderAcceso(fn () => $acceso->registrarSalida($request->lectura()));
    }
}
