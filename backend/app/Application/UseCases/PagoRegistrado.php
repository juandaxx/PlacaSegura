<?php

namespace App\Application\UseCases;

use App\Domain\Cobro\EstadoCobro;
use App\Domain\Cobro\Pago;

/**
 * Resultado de RegistrarPago: el pago y el estado en que quedo el cobro.
 *
 * esNuevo es false cuando la solicitud repetia una referencia ya registrada
 * y se devolvio el pago existente.
 */
final class PagoRegistrado
{
    public function __construct(
        public readonly Pago $pago,
        public readonly EstadoCobro $estadoCobro,
        public readonly bool $esNuevo,
    ) {}
}
