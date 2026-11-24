<?php

namespace App\Domain\Acceso;

/**
 * Ocupacion actual de una sede, por tipo de vehiculo.
 */
final class CuposSede
{
    /**
     * @param  list<OcupacionCupo>  $cupos
     */
    public function __construct(
        public readonly int $sedeId,
        public readonly array $cupos,
    ) {}
}
