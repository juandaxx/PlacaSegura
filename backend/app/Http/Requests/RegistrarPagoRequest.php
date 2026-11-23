<?php

namespace App\Http\Requests;

use App\Domain\Cobro\MedioPago;
use App\Http\Requests\Concerns\IdentificaOperador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos para registrar un pago (POST /api/cobros/{id}/pagos).
 *
 * La referencia identifica el intento de pago: la caja la genera una vez y la
 * reenvia igual si repite la solicitud, para que el pago sea idempotente.
 */
class RegistrarPagoRequest extends FormRequest
{
    use IdentificaOperador;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'medio' => ['required', Rule::enum(MedioPago::class)],
            'referencia' => ['required', 'string', 'max:100'],
            'usuario_id' => $this->reglasUsuarioId(),
        ];
    }

    public function medio(): MedioPago
    {
        return $this->enum('medio', MedioPago::class);
    }
}
