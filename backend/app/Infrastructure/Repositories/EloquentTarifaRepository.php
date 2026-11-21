<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\ConsultaTarifas;
use App\Domain\Contracts\TarifaRepository;
use App\Domain\Tarifa\NombreFranja;
use App\Domain\Tarifa\Tarifa;
use App\Domain\Tarifa\TarifaRechazada;
use App\Models\Franja as FranjaModelo;
use App\Models\Tarifa as TarifaModelo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Tarifas guardadas con los modelos Eloquent. El dominio solo ve la entidad
 * Tarifa, nunca el modelo.
 */
final class EloquentTarifaRepository implements TarifaRepository, ConsultaTarifas
{
    public function buscarPorId(int $id): ?Tarifa
    {
        $modelo = TarifaModelo::with('franja')->find($id);

        return $modelo === null ? null : $this->aTarifa($modelo);
    }

    public function nombreFranja(int $franjaId): ?NombreFranja
    {
        $nombre = FranjaModelo::whereKey($franjaId)->value('nombre');

        return $nombre === null ? null : NombreFranja::from($nombre);
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

        return $this->aTarifa($modelo->fresh('franja'));
    }

    public function actualizarValor(int $id, string $valor): Tarifa
    {
        $modelo = TarifaModelo::with('franja')->findOrFail($id);
        $modelo->update(['valor' => $valor]);

        return $this->aTarifa($modelo->fresh('franja'));
    }

    public function listar(?int $sedeId = null, ?int $tipoVehiculoId = null): array
    {
        return TarifaModelo::with('franja')
            ->when($sedeId !== null, fn (Builder $consulta) => $consulta->where('sede_id', $sedeId))
            ->when($tipoVehiculoId !== null, fn (Builder $consulta) => $consulta->where('tipo_vehiculo_id', $tipoVehiculoId))
            ->orderBy('sede_id')
            ->orderBy('tipo_vehiculo_id')
            ->orderBy('franja_id')
            ->get()
            ->map(fn (TarifaModelo $modelo) => $this->aTarifa($modelo))
            ->all();
    }

    private function aTarifa(TarifaModelo $modelo): Tarifa
    {
        return new Tarifa(
            id: (int) $modelo->id,
            sedeId: (int) $modelo->sede_id,
            tipoVehiculoId: (int) $modelo->tipo_vehiculo_id,
            franjaId: (int) $modelo->franja_id,
            franja: NombreFranja::from($modelo->franja->nombre),
            valor: (string) $modelo->valor,
        );
    }
}
