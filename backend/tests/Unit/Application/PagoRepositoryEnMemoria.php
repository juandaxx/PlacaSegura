<?php

namespace Tests\Unit\Application;

use App\Domain\Cobro\Pago;
use App\Domain\Contracts\PagoRepository;

/**
 * Doble de prueba: guarda pagos en un arreglo, sin base de datos.
 */
final class PagoRepositoryEnMemoria implements PagoRepository
{
    /** @var array<int, Pago> */
    public array $pagos = [];

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
}
