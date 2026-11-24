<?php

namespace App\Http\Requests;

use App\Domain\Acceso\FiltroEventos;
use App\Domain\Acceso\TipoEvento;
use App\Http\Requests\Concerns\NormalizaPlaca;
use DateTimeImmutable;
use Illuminate\Validation\Rule;

/**
 * Filtros de GET /api/eventos: placa (coincidencia exacta), fecha (dia
 * operativo) y tipo (ingreso o salida), ademas de page y per_page.
 */
class ListarEventosRequest extends ListadoRequest
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
            'tipo' => ['nullable', Rule::enum(TipoEvento::class)],
        ];
    }

    public function filtro(): FiltroEventos
    {
        return new FiltroEventos(
            placa: $this->filled('placa') ? $this->string('placa')->value() : null,
            diaOperativo: $this->filled('fecha') ? new DateTimeImmutable($this->string('fecha')->value()) : null,
            tipo: $this->enum('tipo', TipoEvento::class),
        );
    }
}
