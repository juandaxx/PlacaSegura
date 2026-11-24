<?php

namespace App\Domain\Acceso;

use App\Domain\Vehiculo\TipoVehiculo;

/**
 * Ocupacion de una sede para un tipo de vehiculo (F-07).
 */
final class OcupacionCupo
{
    /**
     * @param  int|null  $total  Cupo configurado; null si no hay limite.
     */
    public function __construct(
        public readonly TipoVehiculo $tipoVehiculo,
        public readonly ?int $total,
        public readonly int $ocupados,
    ) {}

    /**
     * Cupos libres; nunca negativo aunque el cupo se haya reducido por debajo
     * de los vehiculos que estan adentro. Null si no hay limite.
     */
    public function disponibles(): ?int
    {
        return $this->total === null ? null : max(0, $this->total - $this->ocupados);
    }
}
