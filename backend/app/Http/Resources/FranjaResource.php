<?php

namespace App\Http\Resources;

use App\Domain\Tarifa\Franja;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representacion JSON de una franja, con las horas en formato HH:MM.
 *
 * @property-read Franja $resource
 */
class FranjaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'nombre' => $this->resource->nombre->value,
            'hora_inicio' => substr(Franja::normalizarHora($this->resource->horaInicio), 0, 5),
            'hora_fin' => substr(Franja::normalizarHora($this->resource->horaFin), 0, 5),
        ];
    }
}
