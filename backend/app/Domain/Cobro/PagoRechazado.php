<?php

namespace App\Domain\Cobro;

use DomainException;

/**
 * Regla de negocio que impide registrar un pago.
 */
final class PagoRechazado extends DomainException
{
    public const COBRO_NO_ENCONTRADO = 'cobro_no_encontrado';

    public const COBRO_NO_PENDIENTE = 'cobro_no_pendiente';

    public const COBRO_SIN_VALOR = 'cobro_sin_valor';

    public const REFERENCIA_EN_OTRO_COBRO = 'referencia_en_otro_cobro';

    private function __construct(public readonly string $motivo, string $mensaje)
    {
        parent::__construct($mensaje);
    }

    public static function cobroNoEncontrado(int $cobroId): self
    {
        return new self(self::COBRO_NO_ENCONTRADO, "El cobro {$cobroId} no existe.");
    }

    public static function cobroNoPendiente(int $cobroId, EstadoCobro $estado): self
    {
        return new self(
            self::COBRO_NO_PENDIENTE,
            "El cobro {$cobroId} está {$estado->value}; solo se puede pagar un cobro pendiente.",
        );
    }

    public static function cobroSinValor(int $cobroId): self
    {
        return new self(self::COBRO_SIN_VALOR, "El cobro {$cobroId} no tiene valor a pagar.");
    }

    public static function referenciaEnOtroCobro(string $referencia): self
    {
        return new self(
            self::REFERENCIA_EN_OTRO_COBRO,
            "La referencia {$referencia} ya se usó para pagar otro cobro.",
        );
    }
}
