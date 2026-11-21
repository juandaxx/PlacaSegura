<?php

namespace App\Domain\Cobro;

use DateTimeImmutable;

/**
 * Pago de un cobro. Es inmutable: un pago registrado no se modifica.
 */
final class Pago
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $cobroId,
        public readonly int $usuarioId,
        public readonly string $medio,
        public readonly string $referencia,
        public readonly string $valor,
        public readonly DateTimeImmutable $fechaHora,
        public readonly DateTimeImmutable $paseHasta,
    ) {}

    public function conId(int $id): self
    {
        return new self(
            $id,
            $this->cobroId,
            $this->usuarioId,
            $this->medio,
            $this->referencia,
            $this->valor,
            $this->fechaHora,
            $this->paseHasta,
        );
    }
}
