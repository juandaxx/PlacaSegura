<?php

namespace App\Domain\Acceso;

use DomainException;

/**
 * Regla que impide definir los cupos de una sede.
 */
final class CupoRechazado extends DomainException
{
    public const SEDE_NO_ENCONTRADA = 'sede_no_encontrada';

    public const TIPO_VEHICULO_NO_ENCONTRADO = 'tipo_vehiculo_no_encontrado';

    private function __construct(public readonly string $motivo, string $mensaje)
    {
        parent::__construct($mensaje);
    }

    public static function sedeNoEncontrada(int $sedeId): self
    {
        return new self(self::SEDE_NO_ENCONTRADA, "La sede {$sedeId} no existe.");
    }

    public static function tipoVehiculoNoEncontrado(string $tipoVehiculo): self
    {
        return new self(self::TIPO_VEHICULO_NO_ENCONTRADO, "El tipo de vehículo {$tipoVehiculo} no existe.");
    }
}
