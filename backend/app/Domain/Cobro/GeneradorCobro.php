<?php

namespace App\Domain\Cobro;

use App\Domain\Contracts\CobroRepository;
use App\Domain\Contracts\ConsultaTarifas;
use App\Domain\Contracts\FranjaRepository;
use App\Domain\Tarifa\Franja;
use App\Domain\Tiempo\DiaOperativo;
use App\Domain\Vehiculo\TipoVehiculo;
use DateTimeImmutable;

/**
 * F-03: genera el cobro del dia operativo cuando un vehiculo ingresa.
 *
 * - Usa la tarifa del tipo de vehiculo en la franja de la hora de ingreso
 *   (supuesto para RN-05, pendiente de confirmar con el documento 01).
 * - Guarda en el cobro el valor de esa tarifa (RN-07).
 * - Un solo cobro por placa, sede y dia operativo (RN-04), garantizado ademas
 *   por una restriccion unica en la base de datos.
 */
final class GeneradorCobro
{
    public function __construct(
        private readonly FranjaRepository $franjas,
        private readonly ConsultaTarifas $tarifas,
        private readonly CobroRepository $cobros,
        private readonly DiaOperativo $diaOperativo,
    ) {}

    /**
     * @throws CobroNoCalculable si falta configurar la franja o la tarifa.
     */
    public function generar(
        int $sedeId,
        TipoVehiculo $tipoVehiculo,
        string $placa,
        DateTimeImmutable $momento,
        int $eventoIngresoId,
    ): Cobro {
        $franja = $this->franjaDe($momento) ?? throw CobroNoCalculable::sinFranja($momento);

        $tarifa = $this->tarifas->aplicable($sedeId, $tipoVehiculo->id, $franja->id)
            ?? throw CobroNoCalculable::sinTarifa($tipoVehiculo->nombre, $franja->nombre);

        return $this->cobros->crearSiNoExiste(
            sedeId: $sedeId,
            tarifa: $tarifa,
            eventoIngresoId: $eventoIngresoId,
            placa: $placa,
            diaOperativo: $this->diaOperativo->fechaDe($momento),
        );
    }

    private function franjaDe(DateTimeImmutable $momento): ?Franja
    {
        foreach ($this->franjas->todas() as $franja) {
            if ($franja->contiene($momento)) {
                return $franja;
            }
        }

        return null;
    }
}
