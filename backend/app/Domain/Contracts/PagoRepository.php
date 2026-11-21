<?php

namespace App\Domain\Contracts;

use App\Domain\Cobro\EstadoCobro;
use App\Domain\Cobro\Pago;
use App\Domain\Cobro\ResumenCobro;

/**
 * Persistencia que necesita el registro de un pago (escritura).
 *
 * Las consultas de solo lectura estan en ConsultaPagos, para que cada
 * consumidor dependa unicamente de lo que usa.
 */
interface PagoRepository
{
    /**
     * Obtiene el cobro bloqueandolo hasta que termine la transaccion en curso,
     * para que dos cajas no puedan pagar el mismo cobro al mismo tiempo.
     */
    public function obtenerCobroParaPago(int $cobroId): ?ResumenCobro;

    public function buscarPorReferencia(string $referencia): ?Pago;

    /**
     * Guarda el pago y lo devuelve con su id asignado.
     */
    public function registrar(Pago $pago): Pago;

    public function cambiarEstadoCobro(int $cobroId, EstadoCobro $estado): void;
}
