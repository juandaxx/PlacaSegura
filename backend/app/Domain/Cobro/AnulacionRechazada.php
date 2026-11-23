<?php

namespace App\Domain\Cobro;

use DomainException;

/**
 * Regla de negocio que impide anular un cobro.
 */
final class AnulacionRechazada extends DomainException
{
    public const COBRO_NO_ENCONTRADO = 'cobro_no_encontrado';

    public const COBRO_NO_PENDIENTE = 'cobro_no_pendiente';

    private function __construct(public readonly string $motivo, string $mensaje)
    {
        parent::__construct($mensaje);
    }

    public static function cobroNoEncontrado(int $cobroId): self
    {
        return new self(self::COBRO_NO_ENCONTRADO, "El cobro {$cobroId} no existe.");
    }

    public static function cobroNoPendiente(Cobro $cobro): self
    {
        return new self(
            self::COBRO_NO_PENDIENTE,
            "El cobro {$cobro->id} está {$cobro->estado->value}; solo se puede anular un cobro pendiente.",
        );
    }
}
