<?php

namespace Tests\Unit\Application;

use App\Domain\Cobro\EstadoCobro;
use App\Domain\Cobro\Pago;
use App\Domain\Cobro\ResumenCobro;
use App\Domain\Contracts\PagoRepository;
use DateTimeImmutable;

/**
 * Doble de prueba: guarda cobros y pagos en arreglos, sin base de datos.
 */
final class PagoRepositoryEnMemoria implements PagoRepository
{
    /** @var array<int, ResumenCobro> */
    public array $cobros = [];

    /** @var array<int, Pago> */
    public array $pagos = [];

    public function agregarCobro(
        int $id,
        string $valor,
        EstadoCobro $estado = EstadoCobro::Pendiente,
        string $diaOperativo = '2026-11-20',
    ): void {
        $this->cobros[$id] = new ResumenCobro($id, $valor, $estado, new DateTimeImmutable($diaOperativo));
    }

    public function obtenerCobroParaPago(int $cobroId): ?ResumenCobro
    {
        return $this->cobros[$cobroId] ?? null;
    }

    public function buscarPorReferencia(string $referencia): ?Pago
    {
        foreach ($this->pagos as $pago) {
            if ($pago->referencia === $referencia) {
                return $pago;
            }
        }

        return null;
    }

    public function registrar(Pago $pago): Pago
    {
        $guardado = $pago->conId(count($this->pagos) + 1);
        $this->pagos[$guardado->id] = $guardado;

        return $guardado;
    }

    public function cambiarEstadoCobro(int $cobroId, EstadoCobro $estado): void
    {
        $cobro = $this->cobros[$cobroId];
        $this->cobros[$cobroId] = new ResumenCobro($cobro->id, $cobro->valor, $estado, $cobro->diaOperativo);
    }
}
