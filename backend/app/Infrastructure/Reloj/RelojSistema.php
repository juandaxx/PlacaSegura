<?php

namespace App\Infrastructure\Reloj;

use App\Domain\Contracts\Reloj;
use DateTimeImmutable;

/**
 * Hora real del servidor, en la zona horaria configurada en config/app.php.
 */
final class RelojSistema implements Reloj
{
    public function ahora(): DateTimeImmutable
    {
        return new DateTimeImmutable;
    }
}
