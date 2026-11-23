<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\TipoVehiculoRepository;
use App\Models\TipoVehiculo as TipoVehiculoModelo;

/**
 * Tipos de vehiculo con el modelo Eloquent.
 */
final class EloquentTipoVehiculoRepository implements TipoVehiculoRepository
{
    public function todos(): array
    {
        return TipoVehiculoModelo::orderBy('id')
            ->get()
            ->map(fn (TipoVehiculoModelo $modelo) => TipoVehiculoMapeo::aEntidad($modelo))
            ->all();
    }
}
