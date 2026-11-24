<?php

namespace App\Domain\Contracts;

use App\Domain\Acceso\CuposSede;

/**
 * Consulta y administracion de los cupos de una sede (F-07).
 *
 * El carril usa CupoRepository, que no incluye operaciones de administracion.
 */
interface GestionCupos
{
    public function existeSede(int $sedeId): bool;

    /**
     * Ocupacion actual de la sede para cada tipo de vehiculo. Si un tipo no
     * tiene cupo configurado, su total es null (sin limite).
     */
    public function ocupacion(int $sedeId): CuposSede;

    /**
     * Crea o reemplaza el cupo total de la sede para el tipo de vehiculo.
     */
    public function definir(int $sedeId, int $tipoVehiculoId, int $total): void;
}
