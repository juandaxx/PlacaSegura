<?php

namespace App\Http\Requests;

use App\Domain\Cobro\MedioPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos para registrar un pago (POST /api/cobros/{id}/pagos).
 *
 * La referencia identifica el intento de pago: la caja la genera una vez y la
 * reenvia igual si repite la solicitud, para que el pago sea idempotente.
 *
 * El operador es el usuario autenticado. Mientras no exista el login (F-10)
 * se acepta usuario_id en la peticion.
 */
class RegistrarPagoRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'medio' => ['required', Rule::enum(MedioPago::class)],
            'referencia' => ['required', 'string', 'max:100'],
            'usuario_id' => [
                Rule::requiredIf(fn () => $this->user() === null),
                'nullable',
                'integer',
                Rule::exists('usuarios', 'id')->where('activo', true),
            ],
        ];
    }

    public function medio(): MedioPago
    {
        return $this->enum('medio', MedioPago::class);
    }

    public function usuarioId(): int
    {
        return (int) ($this->user()?->getAuthIdentifier() ?? $this->integer('usuario_id'));
    }
}
