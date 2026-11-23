<?php

namespace Tests\Unit\Domain\Acceso;

use App\Domain\Acceso\Ingreso\CadenaValidacionIngreso;
use App\Domain\Acceso\Ingreso\ContextoIngreso;
use App\Domain\Acceso\Ingreso\ValidarConfianza;
use App\Domain\Acceso\Ingreso\ValidarCupo;
use App\Domain\Acceso\Ingreso\ValidarPaseVigente;
use App\Domain\Acceso\MotivoAcceso;
use App\Domain\Acceso\PoliticaConfianza;
use App\Domain\Cobro\Cobro;
use App\Domain\Cobro\EstadoCobro;
use App\Domain\Contracts\ConfiguracionOperativa;
use App\Domain\Contracts\CupoRepository;
use App\Domain\Tarifa\NombreFranja;
use App\Domain\Tarifa\Tarifa;
use App\Domain\Vehiculo\TipoVehiculo;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class CadenaValidacionIngresoTest extends TestCase
{
    public function test_si_ninguna_validacion_decide_la_barrera_abre(): void
    {
        $this->assertSame(MotivoAcceso::Ok, $this->cadena(cupoTotal: 10)->evaluar($this->contexto()));
    }

    public function test_con_confianza_baja_pide_lectura_manual_antes_de_revisar_el_cupo(): void
    {
        $motivo = $this->cadena(cupoTotal: 1, ocupados: 1)->evaluar($this->contexto(confianza: 0.5));

        $this->assertSame(MotivoAcceso::LecturaManual, $motivo);
    }

    public function test_una_lectura_ya_validada_por_el_operador_no_revisa_la_confianza(): void
    {
        $motivo = $this->cadena()->evaluar($this->contexto(confianza: 0.5, lecturaManual: true));

        $this->assertSame(MotivoAcceso::Ok, $motivo);
    }

    public function test_sin_cupo_no_entra_aunque_tenga_pase_vigente(): void
    {
        $motivo = $this->cadena(cupoTotal: 1, ocupados: 1)->evaluar($this->contexto(cobroDelDia: EstadoCobro::Pagado));

        $this->assertSame(MotivoAcceso::SinCupo, $motivo);
    }

    public function test_con_el_cobro_del_dia_pagado_entra_con_pase_vigente(): void
    {
        $motivo = $this->cadena()->evaluar($this->contexto(cobroDelDia: EstadoCobro::Pagado));

        $this->assertSame(MotivoAcceso::PaseVigente, $motivo);
    }

    public function test_un_cobro_del_dia_pendiente_no_es_pase_vigente(): void
    {
        $motivo = $this->cadena()->evaluar($this->contexto(cobroDelDia: EstadoCobro::Pendiente));

        $this->assertSame(MotivoAcceso::Ok, $motivo);
    }

    public function test_sin_cupo_configurado_no_hay_limite(): void
    {
        $motivo = $this->cadena(cupoTotal: null, ocupados: 500)->evaluar($this->contexto());

        $this->assertSame(MotivoAcceso::Ok, $motivo);
    }

    private function cadena(?int $cupoTotal = null, int $ocupados = 0): CadenaValidacionIngreso
    {
        $cupos = new class($cupoTotal, $ocupados) implements CupoRepository
        {
            public function __construct(private readonly ?int $total, private readonly int $ocupados) {}

            public function totalBloqueando(int $sedeId, int $tipoVehiculoId): ?int
            {
                return $this->total;
            }

            public function ocupados(int $sedeId, int $tipoVehiculoId): int
            {
                return $this->ocupados;
            }
        };

        $configuracion = new class implements ConfiguracionOperativa
        {
            public function umbralConfianza(): float
            {
                return 0.8;
            }
        };

        return new CadenaValidacionIngreso(
            new ValidarConfianza(new PoliticaConfianza($configuracion)),
            new ValidarCupo($cupos),
            new ValidarPaseVigente,
        );
    }

    private function contexto(float $confianza = 0.95, bool $lecturaManual = false, ?EstadoCobro $cobroDelDia = null): ContextoIngreso
    {
        $tarifa = new Tarifa(1, 1, 1, 'carro', 1, NombreFranja::Diurna, '5000.00');
        $cobro = $cobroDelDia === null
            ? null
            : new Cobro(1, 1, 'ABC123', new DateTimeImmutable('2026-11-20'), '5000.00', $cobroDelDia, 1, $tarifa);

        return new ContextoIngreso(
            sedeId: 1,
            tipoVehiculo: new TipoVehiculo(1, 'carro', '^[A-Z]{3}[0-9]{3}$'),
            confianza: $confianza,
            lecturaManual: $lecturaManual,
            cobroDelDia: $cobro,
        );
    }
}
