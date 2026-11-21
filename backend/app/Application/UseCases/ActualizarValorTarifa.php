<?php

namespace App\Application\UseCases;

use App\Domain\Contracts\GestorTransacciones;
use App\Domain\Contracts\TarifaRepository;
use App\Domain\Tarifa\PoliticaTarifaNocturna;
use App\Domain\Tarifa\Tarifa;
use App\Domain\Tarifa\TarifaRechazada;

/**
 * F-08: cambia el valor de una tarifa respetando RN-02.
 *
 * Los cobros ya generados no cambian: cada cobro guarda su propio valor (RN-07).
 */
final class ActualizarValorTarifa
{
    public function __construct(
        private readonly TarifaRepository $tarifas,
        private readonly GestorTransacciones $transacciones,
        private readonly PoliticaTarifaNocturna $politica,
    ) {}

    /**
     * @throws TarifaRechazada
     */
    public function ejecutar(int $tarifaId, string $valor): Tarifa
    {
        return $this->transacciones->ejecutar(function () use ($tarifaId, $valor) {
            $tarifa = $this->tarifas->buscarPorId($tarifaId)
                ?? throw TarifaRechazada::noEncontrada($tarifaId);

            $this->politica->validar(
                $tarifa->franja,
                $valor,
                $this->tarifas->valoresEnFranja($tarifa->sedeId, $tarifa->tipoVehiculoId, $tarifa->franja->contraria()),
            );

            return $this->tarifas->actualizarValor($tarifa->id, $valor);
        });
    }
}
