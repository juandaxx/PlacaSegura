<?php

namespace App\Application\UseCases;

use App\Domain\Contracts\GestorTransacciones;
use App\Domain\Contracts\TarifaRepository;
use App\Domain\Tarifa\NombreFranja;
use App\Domain\Tarifa\PoliticaTarifaNocturna;
use App\Domain\Tarifa\Tarifa;
use App\Domain\Tarifa\TarifaRechazada;

/**
 * F-08: el administrador configura la tarifa de un tipo de vehiculo en una
 * franja. Rechaza una nocturna mayor o igual a la diurna (RN-02).
 */
final class CrearTarifa
{
    public function __construct(
        private readonly TarifaRepository $tarifas,
        private readonly GestorTransacciones $transacciones,
        private readonly PoliticaTarifaNocturna $politica,
    ) {}

    /**
     * @param  int|null  $sedeId  Si es null se usa la sede, siempre que solo exista una.
     *
     * @throws TarifaRechazada
     */
    public function ejecutar(?int $sedeId, string $tipoVehiculo, NombreFranja $franja, string $valor): Tarifa
    {
        return $this->transacciones->ejecutar(function () use ($sedeId, $tipoVehiculo, $franja, $valor) {
            $sedeId ??= $this->tarifas->idSedeUnica()
                ?? throw TarifaRechazada::sedeRequerida();

            $tipoVehiculoId = $this->tarifas->idTipoVehiculo($tipoVehiculo)
                ?? throw TarifaRechazada::tipoVehiculoNoEncontrado($tipoVehiculo);

            $franjaId = $this->tarifas->idFranja($franja)
                ?? throw TarifaRechazada::franjaNoEncontrada($franja);

            if ($this->tarifas->existe($sedeId, $tipoVehiculoId, $franjaId)) {
                throw TarifaRechazada::yaExiste();
            }

            $this->politica->validar(
                $franja,
                $valor,
                $this->tarifas->valoresEnFranja($sedeId, $tipoVehiculoId, $franja->contraria()),
            );

            return $this->tarifas->crear(new Tarifa(
                id: null,
                sedeId: $sedeId,
                tipoVehiculoId: $tipoVehiculoId,
                tipoVehiculo: $tipoVehiculo,
                franjaId: $franjaId,
                franja: $franja,
                valor: $valor,
            ));
        });
    }
}
