<?php

namespace App\Http\Controllers\Api;

use App\Application\UseCases\ActualizarValorTarifa;
use App\Application\UseCases\CrearTarifa;
use App\Domain\Contracts\ConsultaTarifas;
use App\Domain\Tarifa\TarifaRechazada;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarTarifaRequest;
use App\Http\Requests\CrearTarifaRequest;
use App\Http\Resources\TarifaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoints de administracion de tarifas (F-08). Solo traduce HTTP: las
 * reglas viven en los casos de uso.
 *
 * No hay delete: los cobros referencian la tarifa con la que se calcularon (RN-07).
 */
class TarifaController extends Controller
{
    /**
     * GET /api/tarifas?sede_id=&tipo_vehiculo_id=
     */
    public function index(Request $request, ConsultaTarifas $tarifas): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'sede_id' => ['nullable', 'integer'],
            'tipo_vehiculo_id' => ['nullable', 'integer'],
        ]);

        return TarifaResource::collection($tarifas->listar(
            sedeId: isset($filtros['sede_id']) ? (int) $filtros['sede_id'] : null,
            tipoVehiculoId: isset($filtros['tipo_vehiculo_id']) ? (int) $filtros['tipo_vehiculo_id'] : null,
        ));
    }

    /**
     * GET /api/tarifas/{id}
     */
    public function show(int $tarifa, ConsultaTarifas $tarifas): TarifaResource
    {
        return new TarifaResource($tarifas->buscarPorId($tarifa) ?? abort(404, 'Tarifa no encontrada.'));
    }

    /**
     * POST /api/tarifas
     */
    public function store(CrearTarifaRequest $request, CrearTarifa $crearTarifa): JsonResponse
    {
        try {
            $tarifa = $crearTarifa->ejecutar(
                sedeId: $request->integer('sede_id'),
                tipoVehiculoId: $request->integer('tipo_vehiculo_id'),
                franjaId: $request->integer('franja_id'),
                valor: (string) $request->input('valor'),
            );
        } catch (TarifaRechazada $rechazo) {
            return $this->respuestaRechazo($rechazo);
        }

        return (new TarifaResource($tarifa))->response()->setStatusCode(201);
    }

    /**
     * PUT /api/tarifas/{id}
     */
    public function update(int $tarifa, ActualizarTarifaRequest $request, ActualizarValorTarifa $actualizarValor): JsonResponse
    {
        try {
            $actualizada = $actualizarValor->ejecutar($tarifa, (string) $request->input('valor'));
        } catch (TarifaRechazada $rechazo) {
            return $this->respuestaRechazo($rechazo);
        }

        return (new TarifaResource($actualizada))->response();
    }

    private function respuestaRechazo(TarifaRechazada $rechazo): JsonResponse
    {
        $estado = match ($rechazo->motivo) {
            TarifaRechazada::NO_ENCONTRADA => 404,
            TarifaRechazada::YA_EXISTE => 409,
            default => 422,
        };

        return response()->json(['message' => $rechazo->getMessage(), 'motivo' => $rechazo->motivo], $estado);
    }
}
