<?php

namespace App\Http\Requests;

use App\Domain\Cobro\MedioPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos para registrar un pago (POST /api/pagos).
 *
 * La referencia identifica el intento de pago: la caja debe generarla una vez
 * y reenviarla igual si repite la solicitud, para que el pago sea idempotente.
 */
class RegistrarPagoRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cobro_id' => ['required', 'integer'],
            // Mientras no exista autenticacion (F-10) el operador llega en la solicitud.
            'usuario_id' => ['required', 'integer', Rule::exists('usuarios', 'id')->where('activo', true)],
            'medio' => ['required', Rule::enum(MedioPago::class)],
            'referencia' => ['required', 'string', 'max:100'],
        ];
    }
}
