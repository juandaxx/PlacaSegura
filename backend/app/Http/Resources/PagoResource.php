<?php

namespace App\Http\Resources;

use App\Domain\Cobro\Pago;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representacion JSON de un pago.
 *
 * @property-read Pago $resource
 */
class PagoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'cobro_id' => $this->resource->cobroId,
            'usuario_id' => $this->resource->usuarioId,
            'medio' => $this->resource->medio,
            'referencia' => $this->resource->referencia,
            'valor' => $this->resource->valor,
            'fecha_hora' => $this->resource->fechaHora->format(DATE_ATOM),
            'pase_hasta' => $this->resource->paseHasta->format(DATE_ATOM),
        ];
    }
}
