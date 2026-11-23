<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Cobro\Cobro;
use App\Domain\Cobro\EstadoCobro;
use App\Domain\Cobro\FiltroCobros;
use App\Domain\Comun\Pagina;
use App\Domain\Contracts\CobroRepository;
use App\Domain\Contracts\ConsultaCobros;
use App\Domain\Tarifa\Tarifa;
use App\Models\Cobro as CobroModelo;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Cobros guardados con los modelos Eloquent. El dominio solo ve la entidad
 * Cobro, nunca el modelo.
 */
final class EloquentCobroRepository implements CobroRepository, ConsultaCobros
{
    private const RELACIONES = ['tarifa.franja', 'tarifa.tipoVehiculo'];

    public function buscarPorId(int $id): ?Cobro
    {
        $modelo = CobroModelo::with(self::RELACIONES)->find($id);

        return $modelo === null ? null : $this->aCobro($modelo);
    }

    public function listar(FiltroCobros $filtro, int $pagina, int $porPagina): Pagina
    {
        $resultado = CobroModelo::with(self::RELACIONES)
            ->when($filtro->placa !== null, fn (Builder $consulta) => $consulta->where('placa', $filtro->placa))
            ->when(
                $filtro->diaOperativo !== null,
                fn (Builder $consulta) => $consulta->where('dia_operativo', $filtro->diaOperativo->format('Y-m-d')),
            )
            ->when($filtro->estado !== null, fn (Builder $consulta) => $consulta->where('estado', $filtro->estado->value))
            ->orderByDesc('dia_operativo')
            ->orderByDesc('id')
            ->paginate(perPage: $porPagina, page: $pagina);

        return Paginacion::aPagina($resultado, $this->aCobro(...));
    }

    public function obtenerParaActualizar(int $id): ?Cobro
    {
        $modelo = CobroModelo::with(self::RELACIONES)->lockForUpdate()->find($id);

        return $modelo === null ? null : $this->aCobro($modelo);
    }

    public function cambiarEstado(int $id, EstadoCobro $estado): void
    {
        CobroModelo::whereKey($id)->update(['estado' => $estado->value]);
    }

    public function buscarDelDia(string $placa, int $sedeId, DateTimeImmutable $diaOperativo): ?Cobro
    {
        $modelo = CobroModelo::with(self::RELACIONES)
            ->where('placa', $placa)
            ->where('sede_id', $sedeId)
            ->where('dia_operativo', $diaOperativo->format('Y-m-d'))
            ->first();

        return $modelo === null ? null : $this->aCobro($modelo);
    }

    public function ultimoDeLaPlaca(string $placa, int $sedeId): ?Cobro
    {
        $modelo = CobroModelo::with(self::RELACIONES)
            ->where('placa', $placa)
            ->where('sede_id', $sedeId)
            ->orderByDesc('dia_operativo')
            ->orderByDesc('id')
            ->first();

        return $modelo === null ? null : $this->aCobro($modelo);
    }

    public function crearSiNoExiste(
        int $sedeId,
        Tarifa $tarifa,
        int $eventoIngresoId,
        string $placa,
        DateTimeImmutable $diaOperativo,
    ): Cobro {
        try {
            // Transaccion anidada (savepoint): si la restriccion unica rechaza el
            // insert, la transaccion principal sigue viva y se lee el cobro existente.
            // insertGetId guarda dia_operativo como Y-m-d en cualquier motor.
            $id = DB::transaction(fn () => CobroModelo::query()->insertGetId([
                'sede_id' => $sedeId,
                'tarifa_id' => $tarifa->id,
                'evento_ingreso_id' => $eventoIngresoId,
                'placa' => $placa,
                'dia_operativo' => $diaOperativo->format('Y-m-d'),
                'valor' => $tarifa->valor,
                'estado' => EstadoCobro::Pendiente->value,
            ]));
        } catch (UniqueConstraintViolationException) {
            return $this->buscarDelDia($placa, $sedeId, $diaOperativo)
                ?? throw new LogicException('La restriccion unica indico que el cobro del dia ya existe.');
        }

        return $this->buscarPorId((int) $id)
            ?? throw new LogicException("No se encontro el cobro {$id} recien creado.");
    }

    private function aCobro(CobroModelo $modelo): Cobro
    {
        return new Cobro(
            id: (int) $modelo->id,
            sedeId: (int) $modelo->sede_id,
            placa: $modelo->placa,
            diaOperativo: $modelo->dia_operativo->toDateTimeImmutable(),
            valor: (string) $modelo->valor,
            estado: EstadoCobro::from($modelo->estado),
            eventoIngresoId: (int) $modelo->evento_ingreso_id,
            tarifa: TarifaMapeo::aEntidad($modelo->tarifa),
        );
    }
}
