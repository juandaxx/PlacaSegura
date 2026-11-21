<?php

namespace App\Domain\Contracts;

use Closure;

/**
 * Ejecuta varias operaciones de persistencia como una sola unidad atomica.
 */
interface GestorTransacciones
{
    /**
     * Si la operacion lanza una excepcion no se guarda ninguno de sus cambios.
     *
     * @template T
     *
     * @param  Closure(): T  $operacion
     * @return T
     */
    public function ejecutar(Closure $operacion): mixed;
}
