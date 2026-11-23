<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Acceso\MotivoAcceso;
use App\Domain\Acceso\TipoEvento;
use App\Domain\Contracts\CupoRepository;
use App\Models\Cupo as CupoModelo;
use App\Models\EventoAcceso as EventoAccesoModelo;

/**
 * Cupos con los modelos Eloquent. La ocupacion se calcula con los eventos del carril.
 */
final class EloquentCupoRepository implements CupoRepository
{
    public function totalBloqueando(int $sedeId, int $tipoVehiculoId): ?int
    {
        $total = CupoModelo::where('sede_id', $sedeId)
            ->where('tipo_vehiculo_id', $tipoVehiculoId)
            ->lockForUpdate()
            ->value('total');

        return $total === null ? null : (int) $total;
    }

    /**
     * Un vehiculo esta adentro si su ultimo paso por la sede con la barrera
     * abierta fue un ingreso.
     */
    public function ocupados(int $sedeId, int $tipoVehiculoId): int
    {
        $ultimoPasoPorPlaca = EventoAccesoModelo::query()
            ->selectRaw('MAX(eventos_acceso.id)')
            ->join('carriles', 'carriles.id', '=', 'eventos_acceso.carril_id')
            ->where('carriles.sede_id', $sedeId)
            ->whereIn('eventos_acceso.motivo', MotivoAcceso::conBarreraAbierta())
            ->groupBy('eventos_acceso.placa');

        return EventoAccesoModelo::query()
            ->whereIn('id', $ultimoPasoPorPlaca)
            ->where('tipo', TipoEvento::Ingreso->value)
            ->where('tipo_vehiculo_id', $tipoVehiculoId)
            ->count();
    }
}
