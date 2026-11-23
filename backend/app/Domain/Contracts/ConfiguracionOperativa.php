<?php

namespace App\Domain\Contracts;

/**
 * Valores configurables de la operacion (tabla configuracion).
 */
interface ConfiguracionOperativa
{
    /**
     * Confianza minima (de 0 a 1) para aceptar una lectura sin validacion manual.
     */
    public function umbralConfianza(): float;
}
