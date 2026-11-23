<?php

namespace App\Http\Requests;

use App\Domain\Cobro\EstadoCobro;
use App\Domain\Cobro\FiltroCobros;
use DateTimeImmutable;
use Illuminate\Validation\Rule;

/**
 * Filtros de GET /api/cobros: placa (coincidencia exacta), fecha (dia
 * operativo del cobro) y estado, ademas de page y per_page.
 */
class ListarCobrosRequest extends ListadoRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'placa' => ['nullable', 'string', 'max:10'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'estado' => ['nullable', Rule::enum(EstadoCobro::class)],
        ];
    }

    public function filtro(): FiltroCobros
    {
        return new FiltroCobros(
            placa: $this->filled('placa') ? $this->string('placa')->value() : null,
            diaOperativo: $this->filled('fecha') ? new DateTimeImmutable($this->string('fecha')->value()) : null,
            estado: $this->enum('estado', EstadoCobro::class),
        );
    }

    /**
     * Las placas se guardan en mayusculas y sin espacios: "abc 123" se busca como "ABC123".
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('placa')) {
            $this->merge(['placa' => strtoupper(str_replace(' ', '', (string) $this->input('placa')))]);
        }
    }
}
