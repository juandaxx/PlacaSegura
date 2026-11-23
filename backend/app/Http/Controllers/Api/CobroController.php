<?php

namespace App\Http\Controllers\Api;

use App\Application\UseCases\AnularCobro;
use App\Domain\Cobro\AnulacionRechazada;
use App\Domain\Contracts\ConsultaCobros;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListarCobrosRequest;
use App\Http\Resources\CobroResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoints de cobros (F-03 y F-09). Los cobros se generan en el ingreso;
 * aqui solo se consultan y se anulan. Solo traduce HTTP.
 */
class CobroController extends Controller
{
    /**
     * GET /api/cobros?placa=&fecha=&estado=&page=&per_page= (operador y administrador)
     */
    public function index(ListarCobrosRequest $request, ConsultaCobros $cobros): AnonymousResourceCollection
    {
        $pagina = $cobros->listar($request->filtro(), $request->pagina(), $request->porPagina());

        return CobroResource::collection($request->paginador($pagina));
    }

    /**
     * GET /api/cobros/{id} (operador y administrador)
     */
    public function show(int $cobro, ConsultaCobros $cobros): CobroResource
    {
        return new CobroResource($cobros->buscarPorId($cobro) ?? abort(404, 'Cobro no encontrado.'));
    }

    /**
     * PATCH /api/cobros/{id}/anular (administrador)
     *
     * 200 con el cobro anulado, tambien si ya lo estaba; 409 si ya se pago o vencio.
     */
    public function anular(int $cobro, AnularCobro $anularCobro): JsonResponse
    {
        try {
            $anulado = $anularCobro->ejecutar($cobro);
        } catch (AnulacionRechazada $rechazo) {
            $estado = $rechazo->motivo === AnulacionRechazada::COBRO_NO_ENCONTRADO ? 404 : 409;

            return response()->json(['message' => $rechazo->getMessage()], $estado);
        }

        return (new CobroResource($anulado))->response();
    }
}
