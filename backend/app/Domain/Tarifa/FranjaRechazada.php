<?php

namespace App\Domain\Tarifa;

use DomainException;

/**
 * Regla que impide cambiar el horario de una franja.
 */
final class FranjaRechazada extends DomainException
{
    public const NO_ENCONTRADA = 'franja_no_encontrada';

    public const CONFIGURACION_INVALIDA = 'configuracion_invalida';

    public const SIN_DURACION = 'sin_duracion';

    public const DIURNA_CRUZA_MEDIANOCHE = 'diurna_cruza_medianoche';

    private function __construct(public readonly string $motivo, string $mensaje)
    {
        parent::__construct($mensaje);
    }

    public static function noEncontrada(int $franjaId): self
    {
        return new self(self::NO_ENCONTRADA, "La franja {$franjaId} no existe.");
    }

    public static function configuracionInvalida(): self
    {
        return new self(
            self::CONFIGURACION_INVALIDA,
            'Debe haber exactamente una franja diurna y una nocturna para ajustar los horarios.',
        );
    }

    public static function sinDuracion(): self
    {
        return new self(
            self::SIN_DURACION,
            'La hora de inicio y la de fin no pueden ser iguales: la otra franja quedaría sin horario.',
        );
    }

    public static function diurnaCruzaMedianoche(): self
    {
        return new self(
            self::DIURNA_CRUZA_MEDIANOCHE,
            'La franja diurna no puede cruzar la medianoche; la nocturna es la que va de la noche a la mañana.',
        );
    }
}
