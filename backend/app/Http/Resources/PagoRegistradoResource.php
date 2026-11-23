<?php

namespace App\Http\Resources;

use App\Application\UseCases\PagoRegistrado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Respuesta de POST /api/cobros/{id}/pagos con el formato de la
 * documentacion de endpoints: sin envoltura "data".
 *
 * @property-read PagoRegistrado $resource
 */
class PagoRegistradoResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'pago_id' => $this->resource->pago->id,
            'cobro_id' => $this->resource->pago->cobroId,
            'estado_cobro' => $this->resource->estadoCobro->value,
            'pase_vigente_hasta' => $this->resource->pago->paseHasta->format(DATE_ATOM),
        ];
    }
}
