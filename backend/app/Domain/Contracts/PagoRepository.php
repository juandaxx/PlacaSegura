<?php

namespace App\Domain\Contracts;

use App\Domain\Cobro\Pago;

/**
 * Persistencia de pagos que necesita el registro de un pago.
 *
 * El cobro se lee y se actualiza con CobroRepository.
 */
interface PagoRepository
{
    public function buscarPorReferencia(string $referencia): ?Pago;

    /**
     * Guarda el pago y lo devuelve con su id asignado.
     */
    public function registrar(Pago $pago): Pago;
}
