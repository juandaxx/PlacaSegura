<?php

namespace App\Http\Controllers\Api;

use App\Application\Services\ServicioAcceso;
use App\Domain\Contracts\ConsultaEventos;
use App\Http\Controllers\Api\Concerns\RespondeResultadoAcceso;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListarEventosRequest;
use App\Http\Requests\ValidarPlacaRequest;
use App\Http\Resources\EventoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Eventos del carril: consulta (F-09) y validacion manual de placas (F-06).
 */
class EventoController extends Controller
{
    use RespondeResultadoAcceso;

    /**
     * GET /api/eventos?placa=&fecha=&tipo=&page=&per_page= (operador y administrador)
     */
    public function index(ListarEventosRequest $request, ConsultaEventos $eventos): AnonymousResourceCollection
    {
        $pagina = $eventos->listar($request->filtro(), $request->pagina(), $request->porPagina());

        return EventoResource::collection($request->paginador($pagina));
    }

    /**
     * GET /api/eventos/{id} (operador y administrador)
     */
    public function show(int $evento, ConsultaEventos $eventos): EventoResource
    {
        return new EventoResource($eventos->buscarPorId($evento) ?? abort(404, 'Evento no encontrado.'));
    }

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
