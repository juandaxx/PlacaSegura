<?php

namespace App\Http\Controllers\Api;

use App\Application\UseCases\ActualizarValorTarifa;
use App\Application\UseCases\CrearTarifa;
use App\Domain\Contracts\ConsultaTarifas;
use App\Domain\Tarifa\NombreFranja;
use App\Domain\Tarifa\TarifaRechazada;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarTarifaRequest;
use App\Http\Requests\CrearTarifaRequest;
use App\Http\Requests\ListadoRequest;
use App\Http\Resources\TarifaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Endpoints de administracion de tarifas (F-08). Solo traduce HTTP: las
 * reglas viven en los casos de uso.
 *
 * No hay delete: los cobros referencian la tarifa con la que se calcularon (RN-07).
 */
class TarifaController extends Controller
{
    /**
     * GET /api/tarifas?page=&per_page= (operador y administrador)
     */
    public function index(ListadoRequest $request, ConsultaTarifas $tarifas): AnonymousResourceCollection
    {
        $pagina = $tarifas->listar($request->pagina(), $request->porPagina());

        return TarifaResource::collection($request->paginador($pagina));
    }

    /**
     * POST /api/tarifas (administrador)
     */
    public function store(CrearTarifaRequest $request, CrearTarifa $crearTarifa): JsonResponse
    {
        try {
            $tarifa = $crearTarifa->ejecutar(
                sedeId: $request->filled('sede_id') ? $request->integer('sede_id') : null,
                tipoVehiculo: $request->string('tipo_vehiculo')->value(),
                franja: $request->enum('franja', NombreFranja::class),
                valor: (string) $request->input('valor'),
            );
        } catch (TarifaRechazada $rechazo) {
            return $this->respuestaRechazo($rechazo);
        }

        return (new TarifaResource($tarifa))->response()->setStatusCode(201);
    }

    /**
     * PUT /api/tarifas/{id} (administrador)
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

    /**
     * Las reglas ligadas a un campo (como RN-02 sobre "valor") se responden
     * como error de validacion: 422 con "message" y "errors".
     */
    private function respuestaRechazo(TarifaRechazada $rechazo): JsonResponse
    {
        $campo = match ($rechazo->motivo) {
            TarifaRechazada::NOCTURNA_NO_MENOR_QUE_DIURNA => 'valor',
            TarifaRechazada::SEDE_REQUERIDA => 'sede_id',
            TarifaRechazada::TIPO_VEHICULO_NO_ENCONTRADO => 'tipo_vehiculo',
            TarifaRechazada::FRANJA_NO_ENCONTRADA => 'franja',
            default => null,
        };

        if ($campo !== null) {
            throw ValidationException::withMessages([$campo => $rechazo->getMessage()]);
        }

        $estado = $rechazo->motivo === TarifaRechazada::NO_ENCONTRADA ? 404 : 409;

        return response()->json(['message' => $rechazo->getMessage()], $estado);
    }
}
