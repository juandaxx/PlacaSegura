<?php

namespace App\Domain\Comun;

/**
 * Una pagina de resultados de un listado.
 *
 * @template T
 */
final class Pagina
{
    /**
     * @param  list<T>  $elementos
     */
    public function __construct(
        public readonly array $elementos,
        public readonly int $total,
        public readonly int $numero,
        public readonly int $porPagina,
    ) {}
}
