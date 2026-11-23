<?php

namespace App\Domain\Acceso;

use App\Domain\Contracts\ConfiguracionOperativa;

/**
 * F-06: una lectura con confianza por debajo del umbral configurado la debe
 * validar el operador antes de abrir la barrera. La usan ingreso y salida.
 */
final class PoliticaConfianza
{
    public function __construct(private readonly ConfiguracionOperativa $configuracion) {}

    public function requiereValidacionManual(float $confianza): bool
    {
        return $confianza < $this->configuracion->umbralConfianza();
    }
}
