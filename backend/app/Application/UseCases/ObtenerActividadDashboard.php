<?php

namespace App\Application\UseCases;

use App\Domain\Contracts\ConsultasDashboard;

final class ObtenerActividadDashboard
{
    public function __construct(private readonly ConsultasDashboard $consultas) {}

    /** @return list<array<string, mixed>> */
    public function ejecutar(int $sedeId, int $limite): array
    {
        return $this->consultas->actividad($sedeId, $limite);
    }
}
