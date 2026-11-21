<?php

namespace App\Domain\Cobro;

use DateTimeImmutable;

/**
 * Datos de un cobro que necesita el registro de su pago.
 */
final class ResumenCobro
{
    public function __construct(
        public readonly int $id,
        public readonly string $valor,
        public readonly EstadoCobro $estado,
        public readonly DateTimeImmutable $diaOperativo,
    ) {}

    /**
     * Un cobro en $0 (por ejemplo, moto con tarifa $0 segun RN-01) no se paga.
     */
    public function requierePago(): bool
    {
        return (float) $this->valor > 0;
    }
}
