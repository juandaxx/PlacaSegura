<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Tarifa\NombreFranja;
use App\Domain\Tarifa\Tarifa;
use App\Models\Tarifa as TarifaModelo;

/**
 * Convierte el modelo Eloquent de tarifa en la entidad del dominio.
 * Lo usan los repositorios de tarifas y de cobros (un cobro incluye su tarifa).
 */
final class TarifaMapeo
{
    /**
     * Relaciones que el modelo debe traer cargadas antes de convertirlo.
     */
    public const RELACIONES = ['franja', 'tipoVehiculo'];

    public static function aEntidad(TarifaModelo $modelo): Tarifa
    {
        return new Tarifa(
            id: (int) $modelo->id,
            sedeId: (int) $modelo->sede_id,
            tipoVehiculoId: (int) $modelo->tipo_vehiculo_id,
            tipoVehiculo: $modelo->tipoVehiculo->nombre,
            franjaId: (int) $modelo->franja_id,
            franja: NombreFranja::from($modelo->franja->nombre),
            valor: (string) $modelo->valor,
        );
    }
}
