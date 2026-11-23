<?php

namespace App\Domain\Acceso;

use App\Domain\Cobro\Cobro;

/**
 * Lo que el carril muestra al operador: el evento registrado, si la barrera
 * abre y el cobro relacionado, si lo hay.
 */
final class ResultadoAcceso
{
    public function __construct(
        public readonly EventoAcceso $evento,
        public readonly ?Cobro $cobro,
    ) {}

    public function barreraAbierta(): bool
    {
        return $this->evento->motivo->abreBarrera();
    }
}
