<?php

namespace App\Http\Resources;

use App\Domain\Acceso\EventoAcceso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representacion JSON de un evento del carril.
 *
 * @property-read EventoAcceso $resource
 */
class EventoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $evento = $this->resource;

        return [
            'id' => $evento->id,
            'sede_id' => $evento->sedeId,
            'carril_id' => $evento->carrilId,
            'placa' => $evento->placa,
            'tipo' => $evento->tipo->value,
            'tipo_vehiculo' => $evento->tipoVehiculo->nombre,
            'fecha_hora' => $evento->fechaHora->format(DATE_ATOM),
            'confianza' => $evento->confianza,
            'lectura_manual' => $evento->lecturaManual,
            'usuario_id' => $evento->usuarioId,
            'motivo' => $evento->motivo->value,
            'barrera' => $evento->motivo->abreBarrera() ? 'abierta' : 'cerrada',
        ];
    }
}
