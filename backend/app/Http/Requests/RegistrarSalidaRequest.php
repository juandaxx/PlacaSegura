<?php

namespace App\Http\Requests;

/**
 * Lectura en un carril de salida (POST /api/accesos/salida).
 */
class RegistrarSalidaRequest extends LecturaPlacaRequest
{
    protected function tipoCarril(): string
    {
        return 'salida';
    }
}
