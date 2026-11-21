<?php

namespace App\Domain\Contracts;

use App\Domain\Tarifa\NombreFranja;
use App\Domain\Tarifa\Tarifa;

/**
 * Persistencia que necesita la administracion de tarifas (escritura).
 */
interface TarifaRepository
{
    public function buscarPorId(int $id): ?Tarifa;

    public function nombreFranja(int $franjaId): ?NombreFranja;

    public function existe(int $sedeId, int $tipoVehiculoId, int $franjaId): bool;

    /**
     * Valores de las tarifas de la misma sede y tipo de vehiculo en la franja indicada.
     *
     * @return list<string>
     */
    public function valoresEnFranja(int $sedeId, int $tipoVehiculoId, NombreFranja $franja): array;

    public function crear(Tarifa $tarifa): Tarifa;

    public function actualizarValor(int $id, string $valor): Tarifa;
}
