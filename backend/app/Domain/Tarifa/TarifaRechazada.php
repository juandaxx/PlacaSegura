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

    public const FRANJA_NO_ENCONTRADA = 'franja_no_encontrada';

    private function __construct(public readonly string $motivo, string $mensaje)
    {
        parent::__construct($mensaje);
    }

    public static function nocturnaNoMenorQueDiurna(string $diurna, string $nocturna): self
    {
        return new self(
            self::NOCTURNA_NO_MENOR_QUE_DIURNA,
            "La tarifa nocturna ({$nocturna}) debe ser menor que la diurna ({$diurna}) (RN-02).",
        );
    }

    public static function yaExiste(): self
    {
        return new self(
            self::YA_EXISTE,
            'Ya existe una tarifa para esa sede, tipo de vehiculo y franja; actualice su valor.',
        );
    }

    public static function noEncontrada(int $tarifaId): self
    {
        return new self(self::NO_ENCONTRADA, "La tarifa {$tarifaId} no existe.");
    }

    public static function franjaNoEncontrada(int $franjaId): self
    {
        return new self(self::FRANJA_NO_ENCONTRADA, "La franja {$franjaId} no existe.");
    }
}
