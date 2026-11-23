<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Cobro\Cobro;
use App\Domain\Cobro\EstadoCobro;
use App\Domain\Cobro\FiltroCobros;
use App\Domain\Comun\Pagina;
use App\Domain\Contracts\CobroRepository;
use App\Domain\Contracts\ConsultaCobros;
use App\Models\Cobro as CobroModelo;
use Illuminate\Database\Eloquent\Builder;

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
