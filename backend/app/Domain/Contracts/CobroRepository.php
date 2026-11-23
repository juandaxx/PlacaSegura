<?php

namespace App\Domain\Contracts;

use App\Domain\Cobro\Cobro;
use App\Domain\Cobro\EstadoCobro;

/**
 * Persistencia que necesitan las operaciones que cambian un cobro (escritura).
 *
 * Las consultas de solo lectura estan en ConsultaCobros.
 */
interface CobroRepository
{
    /**
     * Obtiene el cobro bloqueandolo hasta que termine la transaccion en curso,
     * para que no se pueda pagar y anular al mismo tiempo.
     */
    public function obtenerParaActualizar(int $id): ?Cobro;

    public function cambiarEstado(int $id, EstadoCobro $estado): void;
}
