<?php

namespace App\Application\UseCases;

use App\Application\Services\PeriodoDashboard;
use App\Domain\Contracts\ConsultasDashboard;

final class ObtenerComparativoDashboard
{
    public function __construct(
        private readonly ConsultasDashboard $consultas,
        private readonly PeriodoDashboard $periodos,
    ) {}

    /** @return list<array<string, mixed>> */
    public function ejecutar(string $desde, string $hasta): array
    {
        [$inicio, $finExclusivo] = $this->periodos->limites($desde, $hasta);

        return $this->consultas->comparativo($inicio, $finExclusivo);
    }
}
