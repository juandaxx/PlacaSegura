<?php

namespace App\Domain\Contracts;

use App\Domain\Tarifa\Tarifa;

/**
 * Consultas de solo lectura sobre las tarifas configuradas.
 */
interface ConsultaTarifas
{
    public function buscarPorId(int $id): ?Tarifa;

    /**
     * @return list<Tarifa>
     */
    public function listar(?int $sedeId = null, ?int $tipoVehiculoId = null): array;
}
