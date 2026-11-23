<?php

namespace Tests\Dobles;

use App\Domain\Contracts\Reloj;
use DateTimeImmutable;

/**
 * Doble de prueba: un reloj que marca la hora que la prueba le fije, para
 * simular las 05:50 o las 19:00 sin esperar.
 */
final class RelojFijo implements Reloj
{
    private DateTimeImmutable $momento;

    public function __construct(string $momento)
    {
        $this->fijar($momento);
    }

    public function ahora(): DateTimeImmutable
    {
        return $this->momento;
    }

    public function fijar(string $momento): void
    {
        $this->momento = new DateTimeImmutable($momento);
    }
}
