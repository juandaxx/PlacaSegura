<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\FranjaRepository;
use App\Domain\Contracts\GestionFranjas;
use App\Domain\Tarifa\Franja;
use App\Domain\Tarifa\NombreFranja;
use App\Models\Franja as FranjaModelo;

/**
 * Franjas horarias con el modelo Eloquent.
 */
final class EloquentFranjaRepository implements FranjaRepository, GestionFranjas
{
    public function todas(): array
    {
        return FranjaModelo::orderBy('id')->get()->map($this->aFranja(...))->all();
    }

    public function todasBloqueando(): array
    {
        return FranjaModelo::orderBy('id')->lockForUpdate()->get()->map($this->aFranja(...))->all();
    }

    public function actualizarHorario(Franja $franja): void
    {
        FranjaModelo::whereKey($franja->id)->update([
            'hora_inicio' => $franja->horaInicio,
            'hora_fin' => $franja->horaFin,
        ]);
    }

    private function aFranja(FranjaModelo $modelo): Franja
    {
        return new Franja(
            id: (int) $modelo->id,
            nombre: NombreFranja::from($modelo->nombre),
            horaInicio: (string) $modelo->hora_inicio,
            horaFin: (string) $modelo->hora_fin,
        );
    }
}
