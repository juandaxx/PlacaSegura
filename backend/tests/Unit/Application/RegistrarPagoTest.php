<?php

namespace Tests\Unit\Application;

use App\Application\UseCases\RegistrarPago;
use App\Domain\Cobro\EstadoCobro;
use App\Domain\Cobro\MedioPago;
use App\Domain\Cobro\PagoRechazado;
use App\Domain\Contracts\Reloj;
use App\Domain\Tiempo\DiaOperativo;
use Closure;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class RegistrarPagoTest extends TestCase
{
    private CobroRepositoryEnMemoria $cobros;

    private PagoRepositoryEnMemoria $pagos;

    private RegistrarPago $registrarPago;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cobros = new CobroRepositoryEnMemoria;
        $this->pagos = new PagoRepositoryEnMemoria;

        $this->registrarPago = new RegistrarPago(
            $this->cobros,
            $this->pagos,
            new TransaccionInmediata,
            new class implements Reloj
            {
                public function ahora(): DateTimeImmutable
                {
                    return new DateTimeImmutable('2026-11-20 19:00:00');
                }
            },
            new DiaOperativo('06:00'),
        );
    }

    public function test_registra_el_pago_con_pase_vigente_hasta_el_fin_del_dia_operativo(): void
    {
        $this->cobros->agregar(1);

        $resultado = $this->registrarPago->ejecutar(1, 7, MedioPago::Efectivo, 'REF-001');

        $this->assertTrue($resultado->esNuevo);
        $this->assertSame(EstadoCobro::Pagado, $resultado->estadoCobro);
        $this->assertSame('5000.00', $resultado->pago->valor);
        $this->assertSame(7, $resultado->pago->usuarioId);
        $this->assertSame('efectivo', $resultado->pago->medio);
        $this->assertSame('2026-11-20 19:00:00', $resultado->pago->fechaHora->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-21 05:59:59', $resultado->pago->paseHasta->format('Y-m-d H:i:s'));
        $this->assertSame(EstadoCobro::Pagado, $this->cobros->cobros[1]->estado);
    }

    public function test_confirmar_dos_veces_con_la_misma_referencia_no_duplica_el_pago(): void
    {
        $this->cobros->agregar(1);

        $primero = $this->registrarPago->ejecutar(1, 7, MedioPago::Efectivo, 'REF-001');
        $segundo = $this->registrarPago->ejecutar(1, 7, MedioPago::Efectivo, 'REF-001');

        $this->assertFalse($segundo->esNuevo);
        $this->assertSame($primero->pago->id, $segundo->pago->id);
        $this->assertSame(EstadoCobro::Pagado, $segundo->estadoCobro);
        $this->assertCount(1, $this->pagos->pagos);
    }

    public function test_rechaza_una_referencia_ya_usada_en_otro_cobro(): void
    {
        $this->cobros->agregar(1);
        $this->cobros->agregar(2);
        $this->registrarPago->ejecutar(1, 7, MedioPago::Efectivo, 'REF-001');

        $this->assertRechazo(
            PagoRechazado::REFERENCIA_EN_OTRO_COBRO,
            fn () => $this->registrarPago->ejecutar(2, 7, MedioPago::Efectivo, 'REF-001'),
        );
        $this->assertSame(EstadoCobro::Pendiente, $this->cobros->cobros[2]->estado);
    }

    public function test_un_cobro_ya_pagado_no_acepta_un_segundo_pago(): void
    {
        $this->cobros->agregar(1);
        $this->registrarPago->ejecutar(1, 7, MedioPago::Efectivo, 'REF-001');

        $this->assertRechazo(
            PagoRechazado::COBRO_NO_PENDIENTE,
            fn () => $this->registrarPago->ejecutar(1, 7, MedioPago::Efectivo, 'REF-002'),
        );
        $this->assertCount(1, $this->pagos->pagos);
    }

    public function test_rechaza_un_cobro_anulado(): void
    {
        $this->cobros->agregar(1, EstadoCobro::Anulado);

        $this->assertRechazo(
            PagoRechazado::COBRO_NO_PENDIENTE,
            fn () => $this->registrarPago->ejecutar(1, 7, MedioPago::Efectivo, 'REF-001'),
        );
        $this->assertCount(0, $this->pagos->pagos);
    }

    public function test_rechaza_un_cobro_sin_valor_a_pagar(): void
    {
        $this->cobros->agregar(1, valor: '0.00');

        $this->assertRechazo(
            PagoRechazado::COBRO_SIN_VALOR,
            fn () => $this->registrarPago->ejecutar(1, 7, MedioPago::Efectivo, 'REF-001'),
        );
        $this->assertCount(0, $this->pagos->pagos);
    }

    public function test_rechaza_un_cobro_que_no_existe(): void
    {
        $this->assertRechazo(
            PagoRechazado::COBRO_NO_ENCONTRADO,
            fn () => $this->registrarPago->ejecutar(99, 7, MedioPago::Efectivo, 'REF-001'),
        );
    }

    private function assertRechazo(string $motivoEsperado, Closure $accion): void
    {
        try {
            $accion();
        } catch (PagoRechazado $rechazo) {
            $this->assertSame($motivoEsperado, $rechazo->motivo);

            return;
        }

        $this->fail("Se esperaba PagoRechazado con motivo {$motivoEsperado}.");
    }
}
