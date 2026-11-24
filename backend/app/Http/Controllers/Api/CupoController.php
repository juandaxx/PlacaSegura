<?php

namespace App\Http\Controllers\Api;

use App\Application\UseCases\DefinirCupos;
use App\Domain\Acceso\CupoRechazado;
use App\Domain\Contracts\GestionCupos;
use App\Http\Controllers\Controller;
use App\Http\Requests\DefinirCuposRequest;
use App\Http\Resources\CuposSedeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Cupos por sede y tipo de vehiculo (F-07). Solo traduce HTTP.
 */
class CupoController extends Controller
{
    /**
     * GET /api/sedes/{id}/cupos (operador y administrador)
     */
    public function show(int $sede, GestionCupos $cupos): CuposSedeResource
    {
        if (! $cupos->existeSede($sede)) {
            abort(404, "La sede {$sede} no existe.");
        }

        return new CuposSedeResource($cupos->ocupacion($sede));
    }

    /**
     * PUT /api/sedes/{id}/cupos (administrador)
     */
    public function update(int $sede, DefinirCuposRequest $request, DefinirCupos $definirCupos): JsonResponse
    {
        try {
            $cupos = $definirCupos->ejecutar($sede, $request->totalesPorTipo());
        } catch (CupoRechazado $rechazo) {
            if ($rechazo->motivo === CupoRechazado::SEDE_NO_ENCONTRADA) {
                return response()->json(['message' => $rechazo->getMessage()], 404);
            }

            throw ValidationException::withMessages(['cupos' => $rechazo->getMessage()]);
        }

        return (new CuposSedeResource($cupos))->response();
    }
}
