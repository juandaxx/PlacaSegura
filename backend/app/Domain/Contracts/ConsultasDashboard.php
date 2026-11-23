<?php

namespace App\Domain\Contracts;

use DateTimeImmutable;

/** Consultas de solo lectura para el dashboard. */
interface ConsultasDashboard
{
    /** @return array<string, mixed> */
    public function resumen(int $sedeId, DateTimeImmutable $inicio, DateTimeImmutable $finExclusivo, bool $esAdministrador): array;

    /** @return list<array<string, mixed>> */
    public function actividad(int $sedeId, int $limite): array;

    /** @return list<array<string, mixed>> */
    public function comparativo(DateTimeImmutable $inicio, DateTimeImmutable $finExclusivo): array;

    /** @return list<array{id: int, nombre: string}> */
    public function sedes(): array;
}
