<?php

namespace App\Domain\Contracts;

use App\Domain\Cobro\Pago;

/**
 * Consultas de solo lectura sobre los pagos registrados.
 */
interface ConsultaPagos
{
    public function buscarPorId(int $id): ?Pago;

    /**
     * Pagos ordenados del mas reciente al mas antiguo.
     *
     * @param  string|null  $fecha  Fecha del pago en formato Y-m-d.
     * @return list<Pago>
     */
    public function listar(?int $cobroId = null, ?string $fecha = null): array;
}
