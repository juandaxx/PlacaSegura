<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Cobro\EstadoCobro;
use App\Domain\Cobro\Pago;
use App\Domain\Cobro\PagoRechazado;
use App\Domain\Cobro\ResumenCobro;
use App\Domain\Contracts\ConsultaPagos;
use App\Domain\Contracts\PagoRepository;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Pagos guardados en la base de datos (tablas pagos y cobros).
 */
final class DbPagoRepository implements PagoRepository, ConsultaPagos
{
    private const FORMATO_FECHA_HORA = 'Y-m-d H:i:s';

    public function __construct(private readonly ConnectionInterface $db) {}

    public function obtenerCobroParaPago(int $cobroId): ?ResumenCobro
    {
        $fila = $this->db->table('cobros')
            ->select(['id', 'valor', 'estado', 'dia_operativo'])
            ->where('id', $cobroId)
            ->lockForUpdate()
            ->first();

        if ($fila === null) {
            return null;
        }

        return new ResumenCobro(
            id: (int) $fila->id,
            valor: $this->dinero($fila->valor),
            estado: EstadoCobro::from($fila->estado),
            diaOperativo: new DateTimeImmutable($fila->dia_operativo),
        );
    }

    public function buscarPorReferencia(string $referencia): ?Pago
    {
        $fila = $this->db->table('pagos')->where('referencia', $referencia)->first();

        return $fila === null ? null : $this->aPago($fila);
    }

    public function registrar(Pago $pago): Pago
    {
        try {
            $id = $this->db->table('pagos')->insertGetId([
                'cobro_id' => $pago->cobroId,
                'usuario_id' => $pago->usuarioId,
                'medio' => $pago->medio,
                'referencia' => $pago->referencia,
                'valor' => $pago->valor,
                'fecha_hora' => $pago->fechaHora->format(self::FORMATO_FECHA_HORA),
                'pase_hasta' => $pago->paseHasta->format(self::FORMATO_FECHA_HORA),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Otra caja guardo la misma referencia para otro cobro al mismo tiempo.
            throw PagoRechazado::referenciaEnOtroCobro($pago->referencia);
        }

        return $pago->conId((int) $id);
    }

    public function cambiarEstadoCobro(int $cobroId, EstadoCobro $estado): void
    {
        $this->db->table('cobros')->where('id', $cobroId)->update(['estado' => $estado->value]);
    }

    public function buscarPorId(int $id): ?Pago
    {
        $fila = $this->db->table('pagos')->where('id', $id)->first();

        return $fila === null ? null : $this->aPago($fila);
    }

    public function listar(?int $cobroId = null, ?string $fecha = null): array
    {
        return $this->db->table('pagos')
            ->when($cobroId !== null, fn ($consulta) => $consulta->where('cobro_id', $cobroId))
            ->when($fecha !== null, fn ($consulta) => $consulta->whereDate('fecha_hora', $fecha))
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id')
            ->get()
            ->map(fn (object $fila) => $this->aPago($fila))
            ->all();
    }

    private function aPago(object $fila): Pago
    {
        return new Pago(
            id: (int) $fila->id,
            cobroId: (int) $fila->cobro_id,
            usuarioId: (int) $fila->usuario_id,
            medio: $fila->medio,
            referencia: $fila->referencia,
            valor: $this->dinero($fila->valor),
            fechaHora: new DateTimeImmutable($fila->fecha_hora),
            paseHasta: new DateTimeImmutable($fila->pase_hasta),
        );
    }

    /**
     * Normaliza montos a dos decimales ("5000.00"), sin importar el motor de BD.
     */
    private function dinero(int|float|string $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }
}
