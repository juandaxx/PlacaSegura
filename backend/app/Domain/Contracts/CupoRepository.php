<?php

namespace App\Domain\Contracts;

/**
 * Cupos por sede y tipo de vehiculo (F-07).
 */
interface CupoRepository
{
    /**
     * Cupo total de la sede para el tipo de vehiculo; null si no esta
     * configurado. Lo bloquea hasta que termine la transaccion, para que dos
     * ingresos no tomen el ultimo cupo al mismo tiempo.
     */
    public function totalBloqueando(int $sedeId, int $tipoVehiculoId): ?int;

    /**
     * Vehiculos de ese tipo que estan adentro de la sede.
     */
    public function ocupados(int $sedeId, int $tipoVehiculoId): int;
}
