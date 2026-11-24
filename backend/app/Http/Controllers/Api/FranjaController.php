<?php

namespace App\Http\Controllers\Api;

use App\Application\UseCases\CambiarHorarioFranja;
use App\Domain\Contracts\FranjaRepository;
use App\Domain\Tarifa\FranjaRechazada;
use App\Http\Controllers\Controller;
use App\Http\Requests\CambiarHorarioFranjaRequest;
use App\Http\Resources\FranjaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Horarios de las franjas diurna y nocturna (F-08). Solo traduce HTTP.
 */
class FranjaController extends Controller
{
    /**
     * GET /api/franjas (operador y administrador)
     */
    public function index(FranjaRepository $franjas): AnonymousResourceCollection
    {
        return FranjaResource::collection($franjas->todas());
    }

    /**
     * PUT /api/franjas/{id} (administrador)
     *
     * Devuelve las dos franjas: la que se cambio y la que se ajusto.
     */
    public function update(int $franja, CambiarHorarioFranjaRequest $request, CambiarHorarioFranja $cambiarHorario): JsonResponse
    {
        try {
            $franjas = $cambiarHorario->ejecutar(
                $franja,
                $request->string('hora_inicio')->value(),
                $request->string('hora_fin')->value(),
            );
        } catch (FranjaRechazada $rechazo) {
            return $this->respuestaRechazo($rechazo);
        }

        return FranjaResource::collection($franjas)->response();
    }

    /**
     * Los horarios invalidos se responden como error de validacion (422).
     */
    private function respuestaRechazo(FranjaRechazada $rechazo): JsonResponse
    {
        $campo = match ($rechazo->motivo) {
            FranjaRechazada::SIN_DURACION => 'hora_fin',
            FranjaRechazada::DIURNA_CRUZA_MEDIANOCHE => 'hora_inicio',
            default => null,
        };

        if ($campo !== null) {
            throw ValidationException::withMessages([$campo => $rechazo->getMessage()]);
        }

        $estado = $rechazo->motivo === FranjaRechazada::NO_ENCONTRADA ? 404 : 409;

        return response()->json(['message' => $rechazo->getMessage()], $estado);
    }
}
