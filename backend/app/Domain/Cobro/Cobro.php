<?php

namespace App\Domain\Cobro;

use App\Domain\Tarifa\Tarifa;
use DateTimeImmutable;

/**
 * Cobro generado al ingreso de un vehiculo: uno por placa, sede y dia
 * operativo (F-03). Guarda el valor con el que se calculo (RN-07), aunque
 * la tarifa cambie despues.
 */
final class Cobro
{
    public function __construct(
        public readonly int $id,
        public readonly int $sedeId,
        public readonly string $placa,
        public readonly DateTimeImmutable $diaOperativo,
        public readonly string $valor,
        public readonly EstadoCobro $estado,
        public readonly int $eventoIngresoId,
        public readonly Tarifa $tarifa,
    ) {}

    /**
     * Un cobro en $0 (por ejemplo, moto con tarifa $0 segun RN-01) no se paga.
     */
    public function requierePago(): bool
    {
        return (float) $this->valor > 0;
    }

    public function conEstado(EstadoCobro $estado): self
    {
        return new self(
            $this->id,
            $this->sedeId,
            $this->placa,
            $this->diaOperativo,
            $this->valor,
            $estado,
            $this->eventoIngresoId,
            $this->tarifa,
        );
    }
}
