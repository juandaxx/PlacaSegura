<?php

namespace App\Domain\Contracts;

use App\Domain\Vehiculo\TipoVehiculo;

/**
 * Tipos de vehiculo configurados, con el formato de placa de cada uno.
 */
interface TipoVehiculoRepository
{
    /**
     * @return list<TipoVehiculo>
     */
    public function todos(): array;
}
