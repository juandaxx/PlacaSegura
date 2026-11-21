<?php

namespace App\Application\UseCases;

use App\Domain\Contracts\GestorTransacciones;
use App\Domain\Contracts\TarifaRepository;
use App\Domain\Tarifa\PoliticaTarifaNocturna;
use App\Domain\Tarifa\Tarifa;
use App\Domain\Tarifa\TarifaRechazada;

/**
 * F-08: el administrador configura la tarifa de un tipo de vehiculo en una
 * franja de una sede. Rechaza una nocturna mayor o igual a la diurna (RN-02).
 */
final class CrearTarifa
{
    public function __construct(
        private readonly TarifaRepository $tarifas,
        private readonly GestorTransacciones $transacciones,
        private readonly PoliticaTarifaNocturna $politica,
    ) {}

    /**
     * @throws TarifaRechazada
     */
    public function ejecutar(int $sedeId, int $tipoVehiculoId, int $franjaId, string $valor): Tarifa
    {
        return $this->transacciones->ejecutar(function () use ($sedeId, $tipoVehiculoId, $franjaId, $valor) {
            $franja = $this->tarifas->nombreFranja($franjaId)
                ?? throw TarifaRechazada::franjaNoEncontrada($franjaId);

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
                franjaId: $franjaId,
                franja: $franja,
                valor: $valor,
            ));
        });
    }
}
