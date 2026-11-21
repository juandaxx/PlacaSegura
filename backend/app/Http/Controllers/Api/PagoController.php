<?php

namespace App\Http\Controllers\Api;

use App\Application\UseCases\RegistrarPago;
use App\Domain\Cobro\MedioPago;
use App\Domain\Cobro\PagoRechazado;
use App\Domain\Contracts\ConsultaPagos;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarPagoRequest;
use App\Http\Resources\PagoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoints de pagos. Solo traduce HTTP: las reglas viven en RegistrarPago.
 *
 * No hay update ni delete: un pago registrado no se modifica; si hay que
 * deshacer algo se anula el cobro.
 */
class PagoController extends Controller
{
    /**
     * GET /api/pagos?cobro_id=&fecha=Y-m-d
     */
    public function index(Request $request, ConsultaPagos $pagos): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'cobro_id' => ['nullable', 'integer'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return PagoResource::collection($pagos->listar(
            cobroId: isset($filtros['cobro_id']) ? (int) $filtros['cobro_id'] : null,
            fecha: $filtros['fecha'] ?? null,
        ));
    }

    /**
     * GET /api/pagos/{id}
     */
    public function show(int $pago, ConsultaPagos $pagos): PagoResource
    {
        return new PagoResource($pagos->buscarPorId($pago) ?? abort(404, 'Pago no encontrado.'));
    }

    /**
     * POST /api/pagos
     *
     * 201 si se registro un pago nuevo; 200 si la referencia ya estaba
     * registrada para ese cobro (reintento idempotente).
     */
    public function store(RegistrarPagoRequest $request, RegistrarPago $registrarPago): JsonResponse
    {
        try {
            $resultado = $registrarPago->ejecutar(
                cobroId: $request->integer('cobro_id'),
                usuarioId: $request->integer('usuario_id'),
                medio: $request->enum('medio', MedioPago::class),
                referencia: $request->string('referencia')->value(),
            );
        } catch (PagoRechazado $rechazo) {
            return response()->json(
                ['message' => $rechazo->getMessage(), 'motivo' => $rechazo->motivo],
                $rechazo->motivo === PagoRechazado::COBRO_NO_ENCONTRADO ? 404 : 409,
            );
        }

        return (new PagoResource($resultado->pago))
            ->response()
            ->setStatusCode($resultado->esNuevo ? 201 : 200);
    }
}
