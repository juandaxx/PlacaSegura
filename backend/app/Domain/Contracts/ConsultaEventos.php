<?php

namespace App\Domain\Contracts;

use App\Domain\Acceso\EventoAcceso;
use App\Domain\Acceso\FiltroEventos;
use App\Domain\Comun\Pagina;

/**
 * Consultas de solo lectura sobre los eventos del carril (F-09).
 */
interface ConsultaEventos
{
    public function buscarPorId(int $id): ?EventoAcceso;

    /**
     * Eventos del mas reciente al mas antiguo.
     *
     * @return Pagina<EventoAcceso>
     */
    public function listar(FiltroEventos $filtro, int $pagina, int $porPagina): Pagina;
}
