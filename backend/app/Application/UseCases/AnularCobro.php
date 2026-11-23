<?php

namespace App\Application\UseCases;

use App\Domain\Cobro\AnulacionRechazada;
use App\Domain\Cobro\Cobro;
use App\Domain\Cobro\EstadoCobro;
use App\Domain\Contracts\CobroRepository;
use App\Domain\Contracts\GestorTransacciones;

/**
 * El administrador anula un cobro que sigue pendiente.
 *
 * Es idempotente: anular un cobro ya anulado lo devuelve sin cambios, para
 * que un doble clic no se muestre como error.
 */
final class AnularCobro
{
    public function __construct(
        private readonly CobroRepository $cobros,
        private readonly GestorTransacciones $transacciones,
    ) {}

    /**
     * @throws AnulacionRechazada
     */
    public function ejecutar(int $cobroId): Cobro
    {
        return $this->transacciones->ejecutar(function () use ($cobroId) {
            $cobro = $this->cobros->obtenerParaActualizar($cobroId)
                ?? throw AnulacionRechazada::cobroNoEncontrado($cobroId);

            if ($cobro->estado === EstadoCobro::Anulado) {
                return $cobro;
            }

            if ($cobro->estado !== EstadoCobro::Pendiente) {
                throw AnulacionRechazada::cobroNoPendiente($cobro);
            }

            $this->cobros->cambiarEstado($cobro->id, EstadoCobro::Anulado);

            return $cobro->conEstado(EstadoCobro::Anulado);
        });
    }
}
