<?php

namespace App\Http\Requests;

/**
 * Lectura en un carril de entrada (POST /api/accesos/ingreso).
 */
class RegistrarIngresoRequest extends LecturaPlacaRequest
{
    protected function tipoCarril(): string
    {
        return 'entrada';
    }
}
