<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos para crear una tarifa (POST /api/tarifas).
 */
class CrearTarifaRequest extends FormRequest
{
    /**
     * Reglas del valor de una tarifa; las reutiliza ActualizarTarifaRequest.
     */
    public const REGLAS_VALOR = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sede_id' => ['required', 'integer', Rule::exists('sedes', 'id')],
            'tipo_vehiculo_id' => ['required', 'integer', Rule::exists('tipos_vehiculo', 'id')],
            'franja_id' => ['required', 'integer', Rule::exists('franjas', 'id')],
            'valor' => self::REGLAS_VALOR,
        ];
    }
}
