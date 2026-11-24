<?php

namespace App\Domain\Tarifa;

/**
 * Horarios de las franjas (F-08). La diurna y la nocturna deben cubrir las 24
 * horas sin huecos ni cruces, porque cada ingreso necesita una franja para
 * calcular su tarifa.
 *
 * - Al cambiar el horario de una franja, la otra toma el horario complementario:
 *   diurna 07:00-18:00 deja la nocturna en 18:00-07:00.
 * - Se rechaza un horario sin duracion (inicio igual a fin) y una diurna que
 *   cruce la medianoche.
 */
final class PoliticaHorarioFranjas
{
    /**
     * @param  list<Franja>  $franjas  Franjas configuradas.
     * @return list<Franja> La diurna y la nocturna con el horario nuevo.
     *
     * @throws FranjaRechazada
     */
    public function cambiarHorario(array $franjas, int $franjaId, string $horaInicio, string $horaFin): array
    {
        $cambiada = $this->buscar($franjas, $franjaId) ?? throw FranjaRechazada::noEncontrada($franjaId);

        $diurna = $this->unica($franjas, NombreFranja::Diurna);
        $nocturna = $this->unica($franjas, NombreFranja::Nocturna);

        if ($diurna === null || $nocturna === null) {
            throw FranjaRechazada::configuracionInvalida();
        }

        if (Franja::normalizarHora($horaInicio) === Franja::normalizarHora($horaFin)) {
            throw FranjaRechazada::sinDuracion();
        }

        // La otra franja empieza donde termina la cambiada y termina donde esta empieza.
        [$diurna, $nocturna] = $cambiada->nombre === NombreFranja::Diurna
            ? [$diurna->conHorario($horaInicio, $horaFin), $nocturna->conHorario($horaFin, $horaInicio)]
            : [$diurna->conHorario($horaFin, $horaInicio), $nocturna->conHorario($horaInicio, $horaFin)];

        if ($diurna->cruzaMedianoche()) {
            throw FranjaRechazada::diurnaCruzaMedianoche();
        }

        return [$diurna, $nocturna];
    }

    /**
     * @param  list<Franja>  $franjas
     */
    private function buscar(array $franjas, int $franjaId): ?Franja
    {
        foreach ($franjas as $franja) {
            if ($franja->id === $franjaId) {
                return $franja;
            }
        }

        return null;
    }

    /**
     * @param  list<Franja>  $franjas
     */
    private function unica(array $franjas, NombreFranja $nombre): ?Franja
    {
        $encontradas = array_values(array_filter($franjas, fn (Franja $franja) => $franja->nombre === $nombre));

        return count($encontradas) === 1 ? $encontradas[0] : null;
    }
}
