<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Domain\Acceso\AccesoRechazado;
use App\Domain\Acceso\ResultadoAcceso;
use App\Domain\Cobro\CobroNoCalculable;
use App\Http\Resources\ResultadoAccesoResource;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Respuesta comun de los endpoints del carril y traduccion de sus rechazos a HTTP.
 */
trait RespondeResultadoAcceso
{
    /**
     * @param  Closure(): ResultadoAcceso  $operacion
     */
    private function responderAcceso(Closure $operacion): JsonResponse
    {
        try {
            $resultado = $operacion();
        } catch (AccesoRechazado $rechazo) {
            return match ($rechazo->motivo) {
                AccesoRechazado::PLACA_NO_RECONOCIDA => throw ValidationException::withMessages([
                    'placa' => $rechazo->getMessage(),
                ]),
                AccesoRechazado::EVENTO_NO_ENCONTRADO => response()->json(['message' => $rechazo->getMessage()], 404),
                default => response()->json(['message' => $rechazo->getMessage()], 409),
            };
        } catch (CobroNoCalculable $error) {
            // Faltan franjas o tarifas: el administrador debe completar la configuracion.
            return response()->json(['message' => $error->getMessage()], 409);
        }

        return (new ResultadoAccesoResource($resultado))->response();
    }
}
