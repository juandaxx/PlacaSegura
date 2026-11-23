<?php

namespace App\Domain\Acceso\Ingreso;

use App\Domain\Acceso\MotivoAcceso;
use App\Domain\Cobro\EstadoCobro;

/**
 * RN-03: si el cobro del dia operativo ya esta pagado, el vehiculo entra con
 * su pase vigente y no se genera un cobro nuevo.
 */
final class ValidarPaseVigente implements ValidacionIngreso
{
    public function validar(ContextoIngreso $contexto): ?MotivoAcceso
    {
        return $contexto->cobroDelDia?->estado === EstadoCobro::Pagado
            ? MotivoAcceso::PaseVigente
            : null;
    }
}
