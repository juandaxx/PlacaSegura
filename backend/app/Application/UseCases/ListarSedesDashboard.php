<?php

namespace App\Application\UseCases;

use App\Domain\Contracts\ConsultasDashboard;

final class ListarSedesDashboard
{
    public function __construct(private readonly ConsultasDashboard $consultas) {}

    /** @return list<array{id: int, nombre: string}> */
    public function ejecutar(): array
    {
        // TODO: cuando usuarios tenga sede asignada, limitar las sedes disponibles para operadores.
        return $this->consultas->sedes();
    }
}
