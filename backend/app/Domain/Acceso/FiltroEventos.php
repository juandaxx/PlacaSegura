<?php

namespace App\Domain\Acceso;

use DateTimeImmutable;

/**
 * Criterios para listar eventos del carril (F-09). Un criterio en null no filtra.
 */
final class FiltroEventos
{
    public function __construct(
        public readonly ?string $placa = null,
        public readonly ?DateTimeImmutable $diaOperativo = null,
        public readonly ?TipoEvento $tipo = null,
    ) {}
}
