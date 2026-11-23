<?php

namespace App\Domain\Contracts;

use App\Domain\Comun\Pagina;
use App\Domain\Tarifa\Tarifa;

/**
 * Consultas de solo lectura sobre las tarifas configuradas.
 */
interface ConsultaTarifas
{
    /**
     * Tarifas ordenadas por sede, tipo de vehiculo y franja.
     *
     * @return Pagina<Tarifa>
     */
    public function listar(int $pagina, int $porPagina): Pagina;

    /**
     * Tarifa configurada para la sede, el tipo de vehiculo y la franja.
     */
    public function aplicable(int $sedeId, int $tipoVehiculoId, int $franjaId): ?Tarifa;
}
