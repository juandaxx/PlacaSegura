<?php

namespace App\Application\UseCases;

use App\Domain\Acceso\CupoRechazado;
use App\Domain\Acceso\CuposSede;
use App\Domain\Contracts\GestionCupos;
use App\Domain\Contracts\GestorTransacciones;
use App\Domain\Contracts\TipoVehiculoRepository;

/**
 * F-07: el administrador define los cupos de una sede por tipo de vehiculo.
 * Los tipos que no se envian conservan su cupo actual.
 */
final class DefinirCupos
{
    public function __construct(
        private readonly GestionCupos $cupos,
        private readonly TipoVehiculoRepository $tipos,
        private readonly GestorTransacciones $transacciones,
    ) {}

    /**
     * @param  array<string, int>  $totalesPorTipo  Nombre del tipo de vehiculo => cupo total.
     *
     * @throws CupoRechazado
     */
    public function ejecutar(int $sedeId, array $totalesPorTipo): CuposSede
    {
        if (! $this->cupos->existeSede($sedeId)) {
            throw CupoRechazado::sedeNoEncontrada($sedeId);
        }

        $tiposPorNombre = [];

        foreach ($this->tipos->todos() as $tipo) {
            $tiposPorNombre[$tipo->nombre] = $tipo;
        }

        return $this->transacciones->ejecutar(function () use ($sedeId, $totalesPorTipo, $tiposPorNombre) {
            foreach ($totalesPorTipo as $nombre => $total) {
                $tipo = $tiposPorNombre[$nombre] ?? throw CupoRechazado::tipoVehiculoNoEncontrado($nombre);

                $this->cupos->definir($sedeId, $tipo->id, $total);
            }

            return $this->cupos->ocupacion($sedeId);
        });
    }
}
