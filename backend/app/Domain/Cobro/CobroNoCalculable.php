<?php

namespace App\Domain\Cobro;

use App\Domain\Tarifa\NombreFranja;
use DateTimeImmutable;
use DomainException;

/**
 * Falta configuracion para calcular el cobro: la franja horaria o la tarifa.
 */
final class CobroNoCalculable extends DomainException
{
    public static function sinFranja(DateTimeImmutable $momento): self
    {
        return new self("No hay una franja configurada que incluya las {$momento->format('H:i')}.");
    }

    public static function sinTarifa(string $tipoVehiculo, NombreFranja $franja): self
    {
        return new self("No hay tarifa configurada para {$tipoVehiculo} en la franja {$franja->value}.");
    }
}
