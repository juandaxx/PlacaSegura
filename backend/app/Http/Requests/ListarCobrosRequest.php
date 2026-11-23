<?php

namespace App\Http\Requests;

use App\Domain\Cobro\EstadoCobro;
use App\Domain\Cobro\FiltroCobros;
use App\Http\Requests\Concerns\NormalizaPlaca;
use DateTimeImmutable;
use Illuminate\Validation\Rule;

/**
 * Filtros de GET /api/cobros: placa (coincidencia exacta), fecha (dia
 * operativo del cobro) y estado, ademas de page y per_page.
 */
class ListarCobrosRequest extends ListadoRequest
{
    use NormalizaPlaca;

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
}
