<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\FranjaRepository;
use App\Domain\Tarifa\Franja;
use App\Domain\Tarifa\NombreFranja;
use App\Models\Franja as FranjaModelo;

/**
 * Franjas horarias con el modelo Eloquent.
 */
final class EloquentFranjaRepository implements FranjaRepository
{
    public function todas(): array
    {
        return FranjaModelo::orderBy('id')
            ->get()
            ->map(fn (FranjaModelo $modelo) => new Franja(
                id: (int) $modelo->id,
                nombre: NombreFranja::from($modelo->nombre),
                horaInicio: (string) $modelo->hora_inicio,
                horaFin: (string) $modelo->hora_fin,
            ))
            ->all();
    }
}
