<?php

namespace App\Domain\Acceso\Ingreso;

use App\Domain\Acceso\MotivoAcceso;

/**
 * Cadena de validaciones del carril de entrada (Chain of Responsibility), en
 * el orden del enunciado: confianza de la lectura -> cupo -> pase vigente.
 *
 * La primera validacion que decide termina la cadena; si ninguna decide, el
 * vehiculo entra (OK). Agregar una validacion, como anti-passback, es crear
 * una clase nueva y ponerla en la posicion que le corresponde.
 */
final class CadenaValidacionIngreso
{
    /** @var list<ValidacionIngreso> */
    private readonly array $validaciones;

    public function __construct(ValidarConfianza $confianza, ValidarCupo $cupo, ValidarPaseVigente $paseVigente)
    {
        $this->validaciones = [$confianza, $cupo, $paseVigente];
    }

    public function evaluar(ContextoIngreso $contexto): MotivoAcceso
    {
        foreach ($this->validaciones as $validacion) {
            $motivo = $validacion->validar($contexto);

            if ($motivo !== null) {
                return $motivo;
            }
        }

        return MotivoAcceso::Ok;
    }
}
