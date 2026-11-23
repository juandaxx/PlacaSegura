<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\IdentificaOperador;
use App\Http\Requests\Concerns\NormalizaPlaca;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Placa confirmada o corregida por el operador (PATCH /api/eventos/{id}/placa).
 */
class ValidarPlacaRequest extends FormRequest
{
    use IdentificaOperador;
    use NormalizaPlaca;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'placa' => ['required', 'string', 'max:10'],
            'usuario_id' => $this->reglasUsuarioId(),
        ];
    }

    public function placa(): string
    {
        return $this->string('placa')->value();
    }
}
