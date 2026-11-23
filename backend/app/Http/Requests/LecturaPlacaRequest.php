<?php

namespace App\Http\Requests;

use App\Domain\Acceso\LecturaPlaca;
use App\Http\Requests\Concerns\NormalizaPlaca;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Lectura de placa que llega al carril: placa, confianza (de 0 a 1), sede y
 * carril. El carril debe pertenecer a la sede y ser del tipo del endpoint.
 */
abstract class LecturaPlacaRequest extends FormRequest
{
    use NormalizaPlaca;

    /**
     * Tipo de carril que acepta el endpoint: "entrada" o "salida".
     */
    abstract protected function tipoCarril(): string;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'placa' => ['required', 'string', 'max:10'],
            'confianza' => ['required', 'numeric', 'between:0,1'],
            'sede_id' => ['required', 'integer', Rule::exists('sedes', 'id')],
            'carril_id' => [
                'required',
                'integer',
                Rule::exists('carriles', 'id')
                    ->where('sede_id', $this->integer('sede_id'))
                    ->where('tipo', $this->tipoCarril()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'carril_id.exists' => "El carril no es de {$this->tipoCarril()} o no pertenece a la sede.",
        ];
    }

    public function lectura(): LecturaPlaca
    {
        return new LecturaPlaca(
            placa: $this->string('placa')->value(),
            confianza: (float) $this->input('confianza'),
            sedeId: $this->integer('sede_id'),
            carrilId: $this->integer('carril_id'),
        );
    }
}
