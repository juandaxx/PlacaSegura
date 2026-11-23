<?php

namespace App\Domain\Acceso;

/**
 * Sentido del paso por el carril (columna eventos_acceso.tipo).
 */
enum TipoEvento: string
{
    case Ingreso = 'ingreso';
    case Salida = 'salida';
}
