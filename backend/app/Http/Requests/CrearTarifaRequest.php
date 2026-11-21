<?php

namespace App\Http\Requests;

use App\Domain\Tarifa\NombreFranja;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos para crear una tarifa (POST /api/tarifas).
 *
 * El tipo de vehiculo y la franja se envian por nombre ("carro", "nocturna").
 * sede_id es opcional mientras exista una sola sede.
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
            'sede_id' => ['nullable', 'integer', Rule::exists('sedes', 'id')],
            'tipo_vehiculo' => ['required', 'string', Rule::exists('tipos_vehiculo', 'nombre')],
            'franja' => ['required', Rule::enum(NombreFranja::class)],
            'valor' => self::REGLAS_VALOR,
        ];
    }
}
