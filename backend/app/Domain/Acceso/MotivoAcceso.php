<?php

namespace App\Domain\Acceso;

/**
 * Por que la barrera abre o no (seccion 3.3 de la documentacion de endpoints).
 */
enum MotivoAcceso: string
{
    case Ok = 'OK';
    case PaseVigente = 'PASE_VIGENTE';
    case LecturaManual = 'LECTURA_MANUAL';
    case SinCupo = 'SIN_CUPO';
    case CobroPendiente = 'COBRO_PENDIENTE';

    public function abreBarrera(): bool
    {
        return $this === self::Ok || $this === self::PaseVigente;
    }

    /**
     * Motivos con los que el vehiculo paso la barrera.
     *
     * @return list<string>
     */
    public static function conBarreraAbierta(): array
    {
        $abiertos = array_filter(self::cases(), fn (self $motivo) => $motivo->abreBarrera());

        return array_values(array_map(fn (self $motivo) => $motivo->value, $abiertos));
    }
}
