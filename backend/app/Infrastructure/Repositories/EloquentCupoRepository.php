<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Acceso\CuposSede;
use App\Domain\Acceso\MotivoAcceso;
use App\Domain\Acceso\OcupacionCupo;
use App\Domain\Acceso\TipoEvento;
use App\Domain\Contracts\CupoRepository;
use App\Domain\Contracts\GestionCupos;
use App\Models\Cupo as CupoModelo;
use App\Models\EventoAcceso as EventoAccesoModelo;
use App\Models\Sede as SedeModelo;
use App\Models\TipoVehiculo as TipoVehiculoModelo;

/**
 * Cupos con los modelos Eloquent. La ocupacion se calcula con los eventos del
 * carril, con la misma regla para la barrera y para las consultas.
 */
final class EloquentCupoRepository implements CupoRepository, GestionCupos
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

    public function existeSede(int $sedeId): bool
    {
        return SedeModelo::whereKey($sedeId)->exists();
    }

    public function ocupacion(int $sedeId): CuposSede
    {
        $totales = CupoModelo::where('sede_id', $sedeId)->pluck('total', 'tipo_vehiculo_id');

        $cupos = TipoVehiculoModelo::orderBy('id')
            ->get()
            ->map(fn (TipoVehiculoModelo $tipo) => new OcupacionCupo(
                tipoVehiculo: TipoVehiculoMapeo::aEntidad($tipo),
                total: $totales->has($tipo->id) ? (int) $totales[$tipo->id] : null,
                ocupados: $this->ocupados($sedeId, (int) $tipo->id),
            ))
            ->all();

        return new CuposSede($sedeId, $cupos);
    }

    public function definir(int $sedeId, int $tipoVehiculoId, int $total): void
    {
        CupoModelo::updateOrCreate(
            ['sede_id' => $sedeId, 'tipo_vehiculo_id' => $tipoVehiculoId],
            ['total' => $total],
        );
    }
}
