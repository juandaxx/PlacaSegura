<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Comun\Pagina;
use App\Domain\Contracts\ConsultaTarifas;
use App\Domain\Contracts\TarifaRepository;
use App\Domain\Tarifa\NombreFranja;
use App\Domain\Tarifa\Tarifa;
use App\Domain\Tarifa\TarifaRechazada;
use App\Models\Franja as FranjaModelo;
use App\Models\Sede as SedeModelo;
use App\Models\Tarifa as TarifaModelo;
use App\Models\TipoVehiculo as TipoVehiculoModelo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Tarifas guardadas con los modelos Eloquent. El dominio solo ve la entidad
 * Tarifa, nunca el modelo.
 */
final class EloquentTarifaRepository implements TarifaRepository, ConsultaTarifas
{
    private const RELACIONES = ['franja', 'tipoVehiculo'];

    public function buscarPorId(int $id): ?Tarifa
    {
        $modelo = TarifaModelo::with(self::RELACIONES)->find($id);

        return $modelo === null ? null : $this->aTarifa($modelo);
    }

    public function idSedeUnica(): ?int
    {
        $ids = SedeModelo::query()->limit(2)->pluck('id');

        return $ids->count() === 1 ? (int) $ids->first() : null;
    }

    public function idTipoVehiculo(string $nombre): ?int
    {
        $id = TipoVehiculoModelo::where('nombre', $nombre)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function idFranja(NombreFranja $franja): ?int
    {
        $id = FranjaModelo::where('nombre', $franja->value)->orderBy('id')->value('id');

        return $id === null ? null : (int) $id;
    }

    public function existe(int $sedeId, int $tipoVehiculoId, int $franjaId): bool
    {
        return TarifaModelo::where('sede_id', $sedeId)
            ->where('tipo_vehiculo_id', $tipoVehiculoId)
            ->where('franja_id', $franjaId)
            ->exists();
    }

    public function valoresEnFranja(int $sedeId, int $tipoVehiculoId, NombreFranja $franja): array
    {
        return TarifaModelo::where('sede_id', $sedeId)
            ->where('tipo_vehiculo_id', $tipoVehiculoId)
            ->whereHas('franja', fn (Builder $consulta) => $consulta->where('nombre', $franja->value))
            ->get()
            ->map(fn (TarifaModelo $modelo) => (string) $modelo->valor)
            ->all();
    }

    public function crear(Tarifa $tarifa): Tarifa
    {
        try {
            $modelo = TarifaModelo::create([
                'sede_id' => $tarifa->sedeId,
                'tipo_vehiculo_id' => $tarifa->tipoVehiculoId,
                'franja_id' => $tarifa->franjaId,
                'valor' => $tarifa->valor,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Otro administrador creo la misma tarifa al mismo tiempo.
            throw TarifaRechazada::yaExiste();
        }

        return $this->aTarifa($modelo->fresh(self::RELACIONES));
    }

    public function actualizarValor(int $id, string $valor): Tarifa
    {
        $modelo = TarifaModelo::findOrFail($id);
        $modelo->update(['valor' => $valor]);

        return $this->aTarifa($modelo->fresh(self::RELACIONES));
    }

    public function listar(int $pagina, int $porPagina): Pagina
    {
        $resultado = TarifaModelo::with(self::RELACIONES)
            ->orderBy('sede_id')
            ->orderBy('tipo_vehiculo_id')
            ->orderBy('franja_id')
            ->orderBy('id')
            ->paginate(perPage: $porPagina, page: $pagina);

        return new Pagina(
            elementos: $resultado->getCollection()->map(fn (TarifaModelo $modelo) => $this->aTarifa($modelo))->all(),
            total: $resultado->total(),
            numero: $resultado->currentPage(),
            porPagina: $resultado->perPage(),
        );
    }

    private function aTarifa(TarifaModelo $modelo): Tarifa
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
