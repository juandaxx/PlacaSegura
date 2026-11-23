<?php

namespace App\Domain\Acceso;

use App\Domain\Vehiculo\TipoVehiculo;
use DateTimeImmutable;

/**
 * Paso (o intento de paso) de un vehiculo por un carril, con la decision
 * que se tomo sobre la barrera.
 */
final class EventoAcceso
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $sedeId,
        public readonly int $carrilId,
        public readonly TipoVehiculo $tipoVehiculo,
        public readonly ?int $usuarioId,
        public readonly string $placa,
        public readonly TipoEvento $tipo,
        public readonly ?float $confianza,
        public readonly bool $lecturaManual,
        public readonly DateTimeImmutable $fechaHora,
        public readonly MotivoAcceso $motivo,
    ) {}

    public function conId(int $id): self
    {
        return new self(
            $id,
            $this->sedeId,
            $this->carrilId,
            $this->tipoVehiculo,
            $this->usuarioId,
            $this->placa,
            $this->tipo,
            $this->confianza,
            $this->lecturaManual,
            $this->fechaHora,
            $this->motivo,
        );
    }

    /**
     * F-06: el operador confirma o corrige la placa y el evento queda
     * marcado como lectura manual, con la nueva decision.
     */
    public function validadoPor(int $usuarioId, string $placa, TipoVehiculo $tipoVehiculo, MotivoAcceso $motivo): self
    {
        return new self(
            $this->id,
            $this->sedeId,
            $this->carrilId,
            $tipoVehiculo,
            $usuarioId,
            $placa,
            $this->tipo,
            $this->confianza,
            true,
            $this->fechaHora,
            $motivo,
        );
    }
}
