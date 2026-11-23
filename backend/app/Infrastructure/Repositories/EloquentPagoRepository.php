<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Cobro\Pago;
use App\Domain\Cobro\PagoRechazado;
use App\Domain\Contracts\PagoRepository;
use App\Models\Pago as PagoModelo;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Pagos guardados con el modelo Eloquent. El dominio solo ve la entidad
 * Pago, nunca el modelo.
 */
final class EloquentPagoRepository implements PagoRepository
{
    public function buscarPorReferencia(string $referencia): ?Pago
    {
        $modelo = PagoModelo::where('referencia', $referencia)->first();

        return $modelo === null ? null : $this->aPago($modelo);
    }

    public function registrar(Pago $pago): Pago
    {
        try {
            $modelo = PagoModelo::create([
                'cobro_id' => $pago->cobroId,
                'usuario_id' => $pago->usuarioId,
                'medio' => $pago->medio,
                'referencia' => $pago->referencia,
                'valor' => $pago->valor,
                'fecha_hora' => $pago->fechaHora,
                'pase_hasta' => $pago->paseHasta,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Otra caja guardo la misma referencia para otro cobro al mismo tiempo.
            throw PagoRechazado::referenciaEnOtroCobro($pago->referencia);
        }

        return $pago->conId((int) $modelo->id);
    }

    private function aPago(PagoModelo $modelo): Pago
    {
        return new Pago(
            id: (int) $modelo->id,
            cobroId: (int) $modelo->cobro_id,
            usuarioId: (int) $modelo->usuario_id,
            medio: $modelo->medio,
            referencia: $modelo->referencia,
            valor: (string) $modelo->valor,
            fechaHora: $modelo->fecha_hora->toDateTimeImmutable(),
            paseHasta: $modelo->pase_hasta->toDateTimeImmutable(),
        );
    }
}
