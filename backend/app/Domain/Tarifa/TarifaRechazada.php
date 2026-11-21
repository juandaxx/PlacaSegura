<?php

namespace App\Domain\Tarifa;

use DomainException;

/**
 * Regla de negocio que impide guardar una tarifa.
 */
final class TarifaRechazada extends DomainException
{
    public const NOCTURNA_NO_MENOR_QUE_DIURNA = 'nocturna_no_menor_que_diurna';

    public const YA_EXISTE = 'tarifa_ya_existe';

    public const NO_ENCONTRADA = 'tarifa_no_encontrada';

    public const SEDE_REQUERIDA = 'sede_requerida';

    public const TIPO_VEHICULO_NO_ENCONTRADO = 'tipo_vehiculo_no_encontrado';

    public const FRANJA_NO_ENCONTRADA = 'franja_no_encontrada';

    private function __construct(public readonly string $motivo, string $mensaje)
    {
        parent::__construct($mensaje);
    }

    public static function nocturnaNoMenorQueDiurna(): self
    {
        return new self(self::NOCTURNA_NO_MENOR_QUE_DIURNA, 'La tarifa nocturna debe ser menor que la diurna.');
    }

    public static function yaExiste(): self
    {
        return new self(
            self::YA_EXISTE,
            'Ya existe una tarifa para ese tipo de vehículo y franja; modifíquela con PUT /api/tarifas/{id}.',
        );
    }

    public static function noEncontrada(int $tarifaId): self
    {
        return new self(self::NO_ENCONTRADA, "La tarifa {$tarifaId} no existe.");
    }

    public static function sedeRequerida(): self
    {
        return new self(self::SEDE_REQUERIDA, 'Indique sede_id: no hay una única sede configurada.');
    }

    public static function tipoVehiculoNoEncontrado(string $tipoVehiculo): self
    {
        return new self(self::TIPO_VEHICULO_NO_ENCONTRADO, "El tipo de vehículo {$tipoVehiculo} no existe.");
    }

    public static function franjaNoEncontrada(NombreFranja $franja): self
    {
        return new self(self::FRANJA_NO_ENCONTRADA, "No hay una franja {$franja->value} configurada.");
    }
}
