<?php

namespace App\Http\Resources;

use App\Domain\Acceso\ResultadoAcceso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Respuesta del carril (ingreso, salida y validacion manual) con el formato
 * de la documentacion de endpoints: sin envoltura "data".
 *
 * @property-read ResultadoAcceso $resource
 */
class ResultadoAccesoResource extends JsonResource
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
        $evento = $this->resource->evento;
        $cobro = $this->resource->cobro;

        return [
            'evento_id' => $evento->id,
            'barrera' => $this->resource->barreraAbierta() ? 'abierta' : 'cerrada',
            'motivo' => $evento->motivo->value,
            'placa' => $evento->placa,
            'tipo_vehiculo' => $evento->tipoVehiculo->nombre,
            'lectura_manual' => $evento->lecturaManual,
            'cobro' => $cobro === null ? null : [
                'id' => $cobro->id,
                'valor' => Dinero::aNumero($cobro->valor),
                'estado' => $cobro->estado->value,
            ],
        ];
    }
}
