<?php

namespace Tests\Unit\Application;

use App\Domain\Contracts\GestorTransacciones;
use Closure;

/**
 * Doble de prueba: ejecuta la operacion directamente, sin base de datos.
 */
final class TransaccionInmediata implements GestorTransacciones
{
    public function ejecutar(Closure $operacion): mixed
    {
        return $operacion();
    }
}
