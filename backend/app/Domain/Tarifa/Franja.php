<?php

namespace App\Domain\Tarifa;

use DateTimeImmutable;

/**
 * Franja horaria con tarifa propia. La nocturna cruza la medianoche
 * (por ejemplo, de 18:00 a 06:00).
 */
final class Franja
{
    public function __construct(
        public readonly int $id,
        public readonly NombreFranja $nombre,
        public readonly string $horaInicio,
        public readonly string $horaFin,
    ) {}

    /**
     * El inicio se incluye y el fin no: a las 18:00 ya es nocturna.
     */
    public function contiene(DateTimeImmutable $momento): bool
    {
        $hora = $momento->format('H:i:s');
        $inicio = $this->conSegundos($this->horaInicio);
        $fin = $this->conSegundos($this->horaFin);

        if ($inicio === $fin) {
            return true;
        }

        return $inicio < $fin
            ? $hora >= $inicio && $hora < $fin
            : $hora >= $inicio || $hora < $fin;
    }

    private function conSegundos(string $hora): string
    {
        return strlen($hora) === 5 ? $hora.':00' : $hora;
    }
}
