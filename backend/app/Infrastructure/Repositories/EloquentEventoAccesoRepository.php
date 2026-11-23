<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Acceso\EventoAcceso;
use App\Domain\Acceso\MotivoAcceso;
use App\Domain\Acceso\TipoEvento;
use App\Domain\Contracts\EventoAccesoRepository;
use App\Models\EventoAcceso as EventoAccesoModelo;

/**
 * Eventos del carril con el modelo Eloquent. El dominio solo ve la entidad
 * EventoAcceso, nunca el modelo.
 */
final class EloquentEventoAccesoRepository implements EventoAccesoRepository
{
    private const RELACIONES = ['carril', 'tipoVehiculo'];

    public function registrar(EventoAcceso $evento): EventoAcceso
    {
        // forceFill: la columna motivo todavia no esta en $fillable del modelo.
        $modelo = (new EventoAccesoModelo)->forceFill($this->columnas($evento));
        $modelo->save();

        return $evento->conId((int) $modelo->id);
    }

    public function obtenerParaActualizar(int $id): ?EventoAcceso
    {
        $modelo = EventoAccesoModelo::with(self::RELACIONES)->lockForUpdate()->find($id);

        return $modelo === null ? null : $this->aEvento($modelo);
    }

    public function actualizar(EventoAcceso $evento): void
    {
        EventoAccesoModelo::findOrFail($evento->id)->forceFill($this->columnas($evento))->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function columnas(EventoAcceso $evento): array
    {
        return [
            'carril_id' => $evento->carrilId,
            'tipo_vehiculo_id' => $evento->tipoVehiculo->id,
            'usuario_id' => $evento->usuarioId,
            'placa' => $evento->placa,
            'tipo' => $evento->tipo->value,
            'confianza' => $evento->confianza,
            'lectura_manual' => $evento->lecturaManual,
            'fecha_hora' => $evento->fechaHora,
            'motivo' => $evento->motivo->value,
        ];
    }

    private function aEvento(EventoAccesoModelo $modelo): EventoAcceso
    {
        return new EventoAcceso(
            id: (int) $modelo->id,
            sedeId: (int) $modelo->carril->sede_id,
            carrilId: (int) $modelo->carril_id,
            tipoVehiculo: TipoVehiculoMapeo::aEntidad($modelo->tipoVehiculo),
            usuarioId: $modelo->usuario_id === null ? null : (int) $modelo->usuario_id,
            placa: $modelo->placa,
            tipo: TipoEvento::from($modelo->tipo),
            confianza: $modelo->confianza === null ? null : (float) $modelo->confianza,
            lecturaManual: (bool) $modelo->lectura_manual,
            fechaHora: $modelo->fecha_hora->toDateTimeImmutable(),
            motivo: MotivoAcceso::from($modelo->motivo),
        );
    }
}
