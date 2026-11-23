<?php

namespace App\Application\Services;

use App\Domain\Contracts\Reloj;
use App\Domain\Tiempo\DiaOperativo;
use DateTimeImmutable;

/**
 * Centraliza la conversión de fechas operativas a límites de timestamp.
 */
final class PeriodoDashboard
{
    public function __construct(
        private readonly DiaOperativo $diaOperativo,
        private readonly Reloj $reloj,
    ) {}

    public function hoy(): string
    {
        return $this->diaOperativo->fechaDe($this->reloj->ahora())->format('Y-m-d');
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable} Intervalo [inicio, fin exclusivo].
     */
    public function limites(string $desde, string $hasta): array
    {
        $zona = $this->reloj->ahora()->getTimezone();
        $fechaInicio = DateTimeImmutable::createFromFormat('!Y-m-d', $desde, $zona);
        $fechaFin = DateTimeImmutable::createFromFormat('!Y-m-d', $hasta, $zona);

        $inicio = $this->diaOperativo->finDe($fechaInicio)
            ->modify('-1 day')
            ->modify('+1 second');
        $finExclusivo = $this->diaOperativo->finDe($fechaFin)->modify('+1 second');

        return [$inicio, $finExclusivo];
    }
}
