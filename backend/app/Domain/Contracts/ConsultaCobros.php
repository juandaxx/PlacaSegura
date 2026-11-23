<?php

namespace App\Domain\Contracts;

use App\Domain\Cobro\Cobro;
use App\Domain\Cobro\FiltroCobros;
use App\Domain\Comun\Pagina;

/**
 * Consultas de solo lectura sobre los cobros (F-09).
 */
interface ConsultaCobros
{
    public function buscarPorId(int $id): ?Cobro;

    /**
     * Cobros del dia operativo mas reciente al mas antiguo.
     *
     * @return Pagina<Cobro>
     */
    public function listar(FiltroCobros $filtro, int $pagina, int $porPagina): Pagina;
}
