<?php

namespace App\Infrastructure\Persistencia;

use App\Domain\Contracts\GestorTransacciones;
use Closure;
use Illuminate\Database\ConnectionInterface;

/**
 * Transacciones sobre la conexion de base de datos de Laravel.
 */
final class DbGestorTransacciones implements GestorTransacciones
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function ejecutar(Closure $operacion): mixed
    {
        return $this->db->transaction($operacion);
    }
}
