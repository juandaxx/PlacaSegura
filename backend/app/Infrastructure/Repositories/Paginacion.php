<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Comun\Pagina;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Convierte el paginador de Laravel en la Pagina del dominio, para que los
 * contratos del dominio no dependan de clases de Laravel.
 */
final class Paginacion
{
    /**
     * @template TModelo
     * @template TEntidad
     *
     * @param  LengthAwarePaginator<int, TModelo>  $resultado
     * @param  Closure(TModelo): TEntidad  $aEntidad
     * @return Pagina<TEntidad>
     */
    public static function aPagina(LengthAwarePaginator $resultado, Closure $aEntidad): Pagina
    {
        return new Pagina(
            elementos: array_map($aEntidad, $resultado->items()),
            total: $resultado->total(),
            numero: $resultado->currentPage(),
            porPagina: $resultado->perPage(),
        );
    }
}
