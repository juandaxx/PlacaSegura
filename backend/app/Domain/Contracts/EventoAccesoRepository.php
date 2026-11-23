<?php

namespace App\Domain\Contracts;

use App\Domain\Acceso\EventoAcceso;

/**
 * Persistencia de los eventos del carril (escritura).
 */
interface EventoAccesoRepository
{
    /**
     * Guarda el evento y lo devuelve con su id asignado.
     */
    public function registrar(EventoAcceso $evento): EventoAcceso;

    /**
     * Obtiene el evento bloqueandolo hasta que termine la transaccion en curso,
     * para que dos operadores no validen la misma lectura a la vez.
     */
    public function obtenerParaActualizar(int $id): ?EventoAcceso;

    public function actualizar(EventoAcceso $evento): void;
}
