<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Cupos de una sede por tipo de vehiculo (PUT /api/sedes/{id}/cupos).
 *
 * Ejemplo: {"cupos": [{"tipo_vehiculo": "carro", "total": 50}, {"tipo_vehiculo": "moto", "total": 30}]}
 */
class DefinirCuposRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cupos' => ['required', 'array', 'min:1'],
            'cupos.*.tipo_vehiculo' => ['required', 'string', 'distinct', Rule::exists('tipos_vehiculo', 'nombre')],
            'cupos.*.total' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    /**
     * @return array<string, int> Nombre del tipo de vehiculo => cupo total.
     */
    public function totalesPorTipo(): array
    {
        $totales = [];

        foreach ($this->validated('cupos') as $cupo) {
            $totales[$cupo['tipo_vehiculo']] = (int) $cupo['total'];
        }

        return $totales;
    }
}
