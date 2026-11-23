<?php

namespace App\Domain\Cobro;

use DateTimeImmutable;

/**
 * Criterios para listar cobros (F-09). Un criterio en null no filtra.
 */
final class FiltroCobros
{
    public function __construct(
        public readonly ?string $placa = null,
        public readonly ?DateTimeImmutable $diaOperativo = null,
        public readonly ?EstadoCobro $estado = null,
    ) {}
}
