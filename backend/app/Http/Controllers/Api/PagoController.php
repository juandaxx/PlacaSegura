<?php

namespace App\Http\Controllers\Api;

use App\Application\UseCases\RegistrarPago;
use App\Domain\Cobro\PagoRechazado;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarPagoRequest;
use App\Http\Resources\PagoRegistradoResource;
use Illuminate\Http\JsonResponse;

/**
 * Registro de pagos (F-04). Solo traduce HTTP: las reglas viven en RegistrarPago.
 */
class PagoController extends Controller
{
    /**
     * POST /api/cobros/{id}/pagos (operador)
     *
     * 201 si se registro un pago nuevo; 200 si la referencia ya estaba
     * registrada para ese cobro (reintento idempotente).
     */
    public function store(int $cobro, RegistrarPagoRequest $request, RegistrarPago $registrarPago): JsonResponse
    {
        try {
            $resultado = $registrarPago->ejecutar(
                cobroId: $cobro,
                usuarioId: $request->usuarioId(),
                medio: $request->medio(),
                referencia: $request->string('referencia')->value(),
            );
        } catch (PagoRechazado $rechazo) {
            $estado = $rechazo->motivo === PagoRechazado::COBRO_NO_ENCONTRADO ? 404 : 409;

            return response()->json(['message' => $rechazo->getMessage()], $estado);
        }

        return (new PagoRegistradoResource($resultado))
            ->response()
            ->setStatusCode($resultado->esNuevo ? 201 : 200);
    }
}
