<?php

namespace App\Application\UseCases;

use App\Domain\Cobro\Pago;

/**
 * Resultado de RegistrarPago. esNuevo es false cuando la solicitud repetia
 * una referencia ya registrada y se devolvio el pago existente.
 */
final class PagoRegistrado
{
    public function __construct(
        public readonly Pago $pago,
        public readonly bool $esNuevo,
    ) {}
}
