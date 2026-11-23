<?php

namespace App\Domain\Acceso\Ingreso;

use App\Domain\Acceso\MotivoAcceso;
use App\Domain\Acceso\PoliticaConfianza;

/**
 * F-06: con confianza baja la barrera no abre hasta que el operador valide la placa.
 */
final class ValidarConfianza implements ValidacionIngreso
{
    public function __construct(private readonly PoliticaConfianza $politica) {}

    public function validar(ContextoIngreso $contexto): ?MotivoAcceso
    {
        if ($contexto->lecturaManual) {
            // El operador ya confirmo la placa.
            return null;
        }

        return $this->politica->requiereValidacionManual($contexto->confianza)
            ? MotivoAcceso::LecturaManual
            : null;
    }
}
