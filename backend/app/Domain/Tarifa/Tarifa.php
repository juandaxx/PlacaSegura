<?php

namespace App\Domain\Tarifa;

/**
 * Valor que se cobra en una sede por un tipo de vehiculo en una franja.
 */
final class Tarifa
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $sedeId,
        public readonly int $tipoVehiculoId,
        public readonly int $franjaId,
        public readonly NombreFranja $franja,
        public readonly string $valor,
    ) {}
}
