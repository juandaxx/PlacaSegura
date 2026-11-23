<?php

namespace App\Application\UseCases;

use App\Application\Services\PeriodoDashboard;
use App\Domain\Contracts\ConsultasDashboard;

final class ObtenerResumenDashboard
{
    public function __construct(
        private readonly ConsultasDashboard $consultas,
        private readonly PeriodoDashboard $periodos,
    ) {}

    /** @return array<string, mixed> */
    public function ejecutar(int $sedeId, string $desde, string $hasta, string $rol): array
    {
        [$inicio, $finExclusivo] = $this->periodos->limites($desde, $hasta);

        return $this->consultas->resumen(
            sedeId: $sedeId,
            inicio: $inicio,
            finExclusivo: $finExclusivo,
            esAdministrador: $rol === 'administrador',
        );
    }
}
