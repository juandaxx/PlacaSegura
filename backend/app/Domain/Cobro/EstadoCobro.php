<?php

namespace App\Domain\Cobro;

/**
 * Estados posibles de un cobro (columna cobros.estado).
 */
enum EstadoCobro: string
{
    case Pendiente = 'pendiente';
    case Pagado = 'pagado';
    case Vencido = 'vencido';
    case Anulado = 'anulado';
}
