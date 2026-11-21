<?php

namespace App\Domain\Tarifa;

/**
 * Franjas horarias con tarifa propia (columna franjas.nombre).
 */
enum NombreFranja: string
{
    case Diurna = 'diurna';
    case Nocturna = 'nocturna';

    public function contraria(): self
    {
        return $this === self::Diurna ? self::Nocturna : self::Diurna;
    }
}
