<?php

namespace App\Http\Resources;

use App\Domain\Cobro\Cobro;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representacion JSON de un cobro, con la tarifa con la que se calculo.
 *
 * "valor" es lo que se cobro; la tarifa indica de donde salio (RN-07) y no
 * repite su valor actual, que pudo cambiar despues.
 *
 * @property-read Cobro $resource
 */
class CobroResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cobro = $this->resource;

        return [
            'id' => $cobro->id,
            'sede_id' => $cobro->sedeId,
            'placa' => $cobro->placa,
            'dia_operativo' => $cobro->diaOperativo->format('Y-m-d'),
            'valor' => Dinero::aNumero($cobro->valor),
            'estado' => $cobro->estado->value,
            'evento_ingreso_id' => $cobro->eventoIngresoId,
            'tarifa' => [
                'id' => $cobro->tarifa->id,
                'tipo_vehiculo' => $cobro->tarifa->tipoVehiculo,
                'franja' => $cobro->tarifa->franja->value,
            ],
        ];
    }
}
