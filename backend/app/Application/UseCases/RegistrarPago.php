<?php

namespace App\Application\UseCases;

use App\Domain\Cobro\EstadoCobro;
use App\Domain\Cobro\MedioPago;
use App\Domain\Cobro\Pago;
use App\Domain\Cobro\PagoRechazado;
use App\Domain\Contracts\CobroRepository;
use App\Domain\Contracts\GestorTransacciones;
use App\Domain\Contracts\PagoRepository;
use App\Domain\Contracts\Reloj;
use App\Domain\Tiempo\DiaOperativo;

/**
 * F-04: registra el pago en efectivo de un cobro desde la caja del operador.
 *
 * - Idempotente: repetir la solicitud con la misma referencia devuelve el pago
 *   ya registrado en lugar de crear otro.
 * - Deja un pase vigente hasta el fin del dia operativo del cobro (RN-03).
 */
final class RegistrarPago
{
    public function __construct(
        private readonly CobroRepository $cobros,
        private readonly PagoRepository $pagos,
        private readonly GestorTransacciones $transacciones,
        private readonly Reloj $reloj,
        private readonly DiaOperativo $diaOperativo,
    ) {}

    /**
     * @throws PagoRechazado
     */
    public function ejecutar(int $cobroId, int $usuarioId, MedioPago $medio, string $referencia): PagoRegistrado
    {
        return $this->transacciones->ejecutar(function () use ($cobroId, $usuarioId, $medio, $referencia) {
            // Bloquea el cobro: no se puede pagar dos veces ni anular al mismo tiempo.
            $cobro = $this->cobros->obtenerParaActualizar($cobroId)
                ?? throw PagoRechazado::cobroNoEncontrado($cobroId);

            $pagoExistente = $this->pagos->buscarPorReferencia($referencia);

            if ($pagoExistente !== null) {
                if ($pagoExistente->cobroId !== $cobroId) {
                    throw PagoRechazado::referenciaEnOtroCobro($referencia);
                }

                return new PagoRegistrado($pagoExistente, $cobro->estado, esNuevo: false);
            }

            if ($cobro->estado !== EstadoCobro::Pendiente) {
                throw PagoRechazado::cobroNoPendiente($cobroId, $cobro->estado);
            }

            if (! $cobro->requierePago()) {
                throw PagoRechazado::cobroSinValor($cobroId);
            }

            $pago = $this->pagos->registrar(new Pago(
                id: null,
                cobroId: $cobro->id,
                usuarioId: $usuarioId,
                medio: $medio->value,
                referencia: $referencia,
                valor: $cobro->valor,
                fechaHora: $this->reloj->ahora(),
                paseHasta: $this->diaOperativo->finDe($cobro->diaOperativo),
            ));

            $this->cobros->cambiarEstado($cobro->id, EstadoCobro::Pagado);

            return new PagoRegistrado($pago, EstadoCobro::Pagado, esNuevo: true);
        });
    }
}
