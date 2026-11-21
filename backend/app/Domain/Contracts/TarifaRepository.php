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

    /**
     * Id de la sede cuando existe exactamente una; null si no hay ninguna o hay varias.
     */
    public function idSedeUnica(): ?int;

    public function idTipoVehiculo(string $nombre): ?int;

    public function idFranja(NombreFranja $franja): ?int;

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
