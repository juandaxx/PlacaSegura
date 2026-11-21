<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos para cambiar el valor de una tarifa (PUT /api/tarifas/{id}).
 *
 * Sede, tipo de vehiculo y franja no se cambian: eso seria otra tarifa.
 */
class ActualizarTarifaRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'valor' => CrearTarifaRequest::REGLAS_VALOR,
        ];
    }
}
