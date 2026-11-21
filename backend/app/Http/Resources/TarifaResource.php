<?php

namespace App\Http\Resources;

use App\Domain\Tarifa\Tarifa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representacion JSON de una tarifa.
 *
 * @property-read Tarifa $resource
 */
class TarifaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'sede_id' => $this->resource->sedeId,
            'tipo_vehiculo_id' => $this->resource->tipoVehiculoId,
            'franja_id' => $this->resource->franjaId,
            'franja' => $this->resource->franja->value,
            'valor' => $this->resource->valor,
        ];
    }
}
