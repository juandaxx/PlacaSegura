<?php

namespace App\Domain\Vehiculo;

/**
 * Formato canonico de una placa: mayusculas, sin espacios ni guiones.
 * Ejemplo: "abc-123" se guarda y se busca como "ABC123".
 */
final class Placa
{
    public static function normalizar(string $placa): string
    {
        return strtoupper(preg_replace('/[\s-]+/', '', $placa) ?? $placa);
    }
}
