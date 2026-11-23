<?php

namespace App\Domain\Acceso;

/**
 * Lectura de una placa en un carril, venga de la camara LPR, del simulador
 * o de una imagen. La confianza va de 0 a 1.
 */
final class LecturaPlaca
{
    public function __construct(
        public readonly string $placa,
        public readonly float $confianza,
        public readonly int $sedeId,
        public readonly int $carrilId,
    ) {}
}
