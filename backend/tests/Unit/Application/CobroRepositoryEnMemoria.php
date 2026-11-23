<?php

namespace Tests\Unit\Application;

use App\Domain\Cobro\Cobro;
use App\Domain\Cobro\EstadoCobro;
use App\Domain\Contracts\CobroRepository;
use App\Domain\Tarifa\NombreFranja;
use App\Domain\Tarifa\Tarifa;
use DateTimeImmutable;

/**
 * Doble de prueba: guarda cobros en un arreglo, sin base de datos.
 */
final class CobroRepositoryEnMemoria implements CobroRepository
{
    /** @var array<int, Cobro> */
    public array $cobros = [];

    public function agregar(int $id, EstadoCobro $estado = EstadoCobro::Pendiente, string $valor = '5000.00'): void
    {
        $this->cobros[$id] = new Cobro(
            id: $id,
            sedeId: 1,
            placa: 'ABC123',
            diaOperativo: new DateTimeImmutable('2026-11-20'),
            valor: $valor,
            estado: $estado,
            eventoIngresoId: 1,
            tarifa: new Tarifa(1, 1, 1, 'carro', 1, NombreFranja::Diurna, '5000.00'),
        );
    }

    public function obtenerParaActualizar(int $id): ?Cobro
    {
        return $this->cobros[$id] ?? null;
    }

    public function cambiarEstado(int $id, EstadoCobro $estado): void
    {
        $this->cobros[$id] = $this->cobros[$id]->conEstado($estado);
    }

    public function buscarDelDia(string $placa, int $sedeId, DateTimeImmutable $diaOperativo): ?Cobro
    {
        foreach ($this->cobros as $cobro) {
            if ($cobro->placa === $placa
                && $cobro->sedeId === $sedeId
                && $cobro->diaOperativo->format('Y-m-d') === $diaOperativo->format('Y-m-d')) {
                return $cobro;
            }
        }

        return null;
    }

    public function ultimoDeLaPlaca(string $placa, int $sedeId): ?Cobro
    {
        $ultimo = null;

        foreach ($this->cobros as $cobro) {
            if ($cobro->placa === $placa && $cobro->sedeId === $sedeId) {
                $ultimo = $cobro;
            }
        }

        return $ultimo;
    }

    public function crearSiNoExiste(
        int $sedeId,
        Tarifa $tarifa,
        int $eventoIngresoId,
        string $placa,
        DateTimeImmutable $diaOperativo,
    ): Cobro {
        $existente = $this->buscarDelDia($placa, $sedeId, $diaOperativo);

        if ($existente !== null) {
            return $existente;
        }

        $id = $this->cobros === [] ? 1 : max(array_keys($this->cobros)) + 1;

        return $this->cobros[$id] = new Cobro(
            $id, $sedeId, $placa, $diaOperativo, $tarifa->valor, EstadoCobro::Pendiente, $eventoIngresoId, $tarifa,
        );
    }
}
