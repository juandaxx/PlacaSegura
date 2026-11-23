<?php

namespace App\Domain\Acceso\Ingreso;

use App\Domain\Acceso\MotivoAcceso;
use App\Domain\Contracts\CupoRepository;

/**
 * F-07: sin cupo para el tipo de vehiculo la barrera no abre (la pantalla
 * muestra "LLENO"). Si la sede no tiene cupo configurado para ese tipo, no
 * hay limite.
 */
final class ValidarCupo implements ValidacionIngreso
{
    public function __construct(private readonly CupoRepository $cupos) {}

    public function validar(ContextoIngreso $contexto): ?MotivoAcceso
    {
        $total = $this->cupos->totalBloqueando($contexto->sedeId, $contexto->tipoVehiculo->id);

        if ($total === null) {
            return null;
        }

        $ocupados = $this->cupos->ocupados($contexto->sedeId, $contexto->tipoVehiculo->id);

        return $ocupados >= $total ? MotivoAcceso::SinCupo : null;
    }
}
