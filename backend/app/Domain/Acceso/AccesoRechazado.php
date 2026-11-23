<?php

namespace App\Domain\Acceso;

use DomainException;

/**
 * Situacion en la que el carril no puede procesar la lectura.
 */
final class AccesoRechazado extends DomainException
{
    public const PLACA_NO_RECONOCIDA = 'placa_no_reconocida';

    public const EVENTO_NO_ENCONTRADO = 'evento_no_encontrado';

    public const EVENTO_YA_VALIDADO = 'evento_ya_validado';

    private function __construct(public readonly string $motivo, string $mensaje)
    {
        parent::__construct($mensaje);
    }

    public static function placaNoReconocida(string $placa): self
    {
        return new self(
            self::PLACA_NO_RECONOCIDA,
            "La placa {$placa} no corresponde a ningún tipo de vehículo configurado.",
        );
    }

    public static function eventoNoEncontrado(int $eventoId): self
    {
        return new self(self::EVENTO_NO_ENCONTRADO, "El evento {$eventoId} no existe.");
    }

    public static function eventoYaValidado(int $eventoId): self
    {
        return new self(self::EVENTO_YA_VALIDADO, "El evento {$eventoId} no está pendiente de validación manual.");
    }
}
