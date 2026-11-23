<?php

namespace App\Domain\Acceso\Ingreso;

use App\Domain\Cobro\Cobro;
use App\Domain\Vehiculo\TipoVehiculo;

/**
 * Datos que revisan las validaciones del carril de entrada.
 */
final class ContextoIngreso
{
    public function __construct(
        public readonly int $sedeId,
        public readonly TipoVehiculo $tipoVehiculo,
        public readonly float $confianza,
        public readonly bool $lecturaManual,
        public readonly ?Cobro $cobroDelDia,
    ) {}
}
