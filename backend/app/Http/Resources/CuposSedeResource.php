<?php

namespace App\Http\Resources;

use App\Domain\Acceso\CuposSede;
use App\Domain\Acceso\OcupacionCupo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ocupacion de una sede por tipo de vehiculo, con el formato de la
 * documentacion de endpoints: sin envoltura "data".
 *
 * @property-read CuposSede $resource
 */
class CuposSedeResource extends JsonResource
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
            'sede_id' => $this->resource->sedeId,
            'cupos' => array_map(fn (OcupacionCupo $cupo) => [
                'tipo_vehiculo' => $cupo->tipoVehiculo->nombre,
                'total' => $cupo->total,
                'ocupados' => $cupo->ocupados,
                'disponibles' => $cupo->disponibles(),
            ], $this->resource->cupos),
        ];
    }
}
