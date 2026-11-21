<?php

namespace App\Domain\Contracts;

use DateTimeImmutable;

/**
 * Fuente de la hora actual.
 *
 * Se inyecta en lugar de leer la hora del sistema para que las reglas que
 * dependen de la hora se puedan probar simulando cualquier momento del dia.
 */
interface Reloj
{
    public function ahora(): DateTimeImmutable;
}
