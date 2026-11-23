<?php

namespace App\Http\Controllers\Api;

use App\Application\Services\ServicioAcceso;
use App\Http\Controllers\Api\Concerns\RespondeResultadoAcceso;
use App\Http\Controllers\Controller;
use App\Http\Requests\ValidarPlacaRequest;
use Illuminate\Http\JsonResponse;

/**
 * Eventos del carril. Por ahora, la validacion manual de placas (F-06).
 */
class EventoController extends Controller
{
    use RespondeResultadoAcceso;

    /**
     * PATCH /api/eventos/{id}/placa (operador)
     *
     * Confirma o corrige la placa de una lectura con confianza baja y repite
     * el flujo de ingreso o salida con la placa corregida.
     */
    public function validarPlaca(int $evento, ValidarPlacaRequest $request, ServicioAcceso $acceso): JsonResponse
    {
        return $this->responderAcceso(
            fn () => $acceso->validarLectura($evento, $request->placa(), $request->usuarioId()),
        );
    }
}
