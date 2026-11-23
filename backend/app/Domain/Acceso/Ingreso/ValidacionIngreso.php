<?php

namespace App\Domain\Acceso\Ingreso;

use App\Domain\Acceso\MotivoAcceso;

/**
 * Eslabon de la cadena de validaciones del carril de entrada
 * (Chain of Responsibility): decide el motivo con el que termina el ingreso,
 * o devuelve null para que decida la siguiente validacion.
 */
interface ValidacionIngreso
{
    public function validar(ContextoIngreso $contexto): ?MotivoAcceso;
}
