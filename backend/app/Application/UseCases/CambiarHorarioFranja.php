<?php

namespace App\Application\UseCases;

use App\Domain\Contracts\GestionFranjas;
use App\Domain\Contracts\GestorTransacciones;
use App\Domain\Tarifa\Franja;
use App\Domain\Tarifa\FranjaRechazada;
use App\Domain\Tarifa\PoliticaHorarioFranjas;

/**
 * F-08: el administrador cambia el horario de una franja y la otra se ajusta
 * para cubrir el resto del dia. Los cobros ya generados no cambian (RN-07).
 */
final class CambiarHorarioFranja
{
    public function __construct(
        private readonly GestionFranjas $franjas,
        private readonly PoliticaHorarioFranjas $politica,
        private readonly GestorTransacciones $transacciones,
    ) {}

    /**
     * @return list<Franja> La diurna y la nocturna con su horario nuevo.
     *
     * @throws FranjaRechazada
     */
    public function ejecutar(int $franjaId, string $horaInicio, string $horaFin): array
    {
        return $this->transacciones->ejecutar(function () use ($franjaId, $horaInicio, $horaFin) {
            $franjas = $this->politica->cambiarHorario(
                $this->franjas->todasBloqueando(),
                $franjaId,
                $horaInicio,
                $horaFin,
            );

            foreach ($franjas as $franja) {
                $this->franjas->actualizarHorario($franja);
            }

            return $franjas;
        });
    }
}
