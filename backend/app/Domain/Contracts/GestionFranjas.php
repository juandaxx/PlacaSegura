<?php

namespace App\Domain\Contracts;

use App\Domain\Tarifa\Franja;

/**
 * Administracion de los horarios de las franjas (F-08).
 *
 * El calculo del cobro usa FranjaRepository, que no incluye operaciones de administracion.
 */
interface GestionFranjas
{
    /**
     * Franjas bloqueadas hasta que termine la transaccion, para que dos
     * administradores no cambien los horarios al mismo tiempo.
     *
     * @return list<Franja>
     */
    public function todasBloqueando(): array;

    public function actualizarHorario(Franja $franja): void;
}
