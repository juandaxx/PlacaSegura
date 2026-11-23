<?php

namespace App\Domain\Contracts;

use App\Domain\Cobro\Cobro;
use App\Domain\Cobro\EstadoCobro;
use App\Domain\Tarifa\Tarifa;
use DateTimeImmutable;

/**
 * Persistencia que necesitan las operaciones que crean o cambian un cobro.
 *
 * Las consultas para listados estan en ConsultaCobros.
 */
interface CobroRepository
{
    /**
     * Obtiene el cobro bloqueandolo hasta que termine la transaccion en curso,
     * para que no se pueda pagar y anular al mismo tiempo.
     */
    public function obtenerParaActualizar(int $id): ?Cobro;

    public function cambiarEstado(int $id, EstadoCobro $estado): void;

    /**
     * Cobro de la placa en la sede para ese dia operativo (RN-04: hay a lo sumo uno).
     */
    public function buscarDelDia(string $placa, int $sedeId, DateTimeImmutable $diaOperativo): ?Cobro;

    /**
     * Cobro mas reciente de la placa en la sede, de cualquier dia operativo.
     */
    public function ultimoDeLaPlaca(string $placa, int $sedeId): ?Cobro;

    /**
     * Crea el cobro pendiente con el valor de la tarifa. Si otra lectura ya
     * creo el cobro de ese dia (restriccion unica placa + sede + dia
     * operativo), devuelve ese en lugar de fallar.
     */
    public function crearSiNoExiste(
        int $sedeId,
        Tarifa $tarifa,
        int $eventoIngresoId,
        string $placa,
        DateTimeImmutable $diaOperativo,
    ): Cobro;
}
