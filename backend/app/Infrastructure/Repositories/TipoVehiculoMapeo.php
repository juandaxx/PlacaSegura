<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Vehiculo\TipoVehiculo;
use App\Models\TipoVehiculo as TipoVehiculoModelo;

/**
 * Convierte el modelo Eloquent de tipo de vehiculo en la entidad del dominio.
 */
final class TipoVehiculoMapeo
{
    public static function aEntidad(TipoVehiculoModelo $modelo): TipoVehiculo
    {
        return new TipoVehiculo((int) $modelo->id, $modelo->nombre, $modelo->patron_placa);
    }
}
