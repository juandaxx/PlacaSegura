<?php

namespace App\Http\Resources;

/**
 * Formato de los montos en las respuestas: numero JSON, como en la
 * documentacion de endpoints ("valor": 5000).
 */
final class Dinero
{
    public static function aNumero(string $valor): int|float
    {
        $numero = round((float) $valor, 2);

        return floor($numero) === $numero ? (int) $numero : $numero;
    }
}
