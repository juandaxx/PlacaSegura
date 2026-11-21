<?php

namespace App\Domain\Cobro;

/**
 * Medios de pago aceptados. El MVP exige como minimo efectivo desde la caja
 * del operador (F-04); un medio nuevo se agrega como un caso mas.
 */
enum MedioPago: string
{
    case Efectivo = 'efectivo';
}
