<?php

namespace Tests\Unit\Domain\Tiempo;

use App\Domain\Tiempo\DiaOperativo;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DiaOperativoTest extends TestCase
{
    public function test_el_dia_operativo_termina_a_las_055959_del_dia_siguiente(): void
    {
        $fin = (new DiaOperativo('06:00'))->finDe(new DateTimeImmutable('2026-11-20'));

        $this->assertSame('2026-11-21 05:59:59', $fin->format('Y-m-d H:i:s'));
    }

    public function test_antes_de_las_seis_pertenece_al_dia_operativo_anterior(): void
    {
        $fecha = (new DiaOperativo)->fechaDe(new DateTimeImmutable('2026-11-21 05:59:59'));

        $this->assertSame('2026-11-20', $fecha->format('Y-m-d'));
    }

    public function test_desde_las_seis_pertenece_al_mismo_dia_operativo(): void
    {
        $fecha = (new DiaOperativo)->fechaDe(new DateTimeImmutable('2026-11-21 06:00:00'));

        $this->assertSame('2026-11-21', $fecha->format('Y-m-d'));
    }

    public function test_rechaza_una_hora_de_inicio_invalida(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DiaOperativo('6am');
    }
}
