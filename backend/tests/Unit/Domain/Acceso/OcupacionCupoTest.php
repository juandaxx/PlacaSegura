<?php

namespace Tests\Unit\Domain\Acceso;

use App\Domain\Acceso\OcupacionCupo;
use App\Domain\Vehiculo\TipoVehiculo;
use PHPUnit\Framework\TestCase;

class OcupacionCupoTest extends TestCase
{
    public function test_los_disponibles_son_el_total_menos_los_ocupados(): void
    {
        $this->assertSame(18, $this->ocupacion(total: 30, ocupados: 12)->disponibles());
    }

    public function test_los_disponibles_nunca_son_negativos(): void
    {
        $this->assertSame(0, $this->ocupacion(total: 10, ocupados: 15)->disponibles());
    }

    public function test_sin_cupo_configurado_no_hay_limite(): void
    {
        $this->assertNull($this->ocupacion(total: null, ocupados: 7)->disponibles());
    }

    private function ocupacion(?int $total, int $ocupados): OcupacionCupo
    {
        return new OcupacionCupo(new TipoVehiculo(1, 'carro', '^[A-Z]{3}[0-9]{3}$'), $total, $ocupados);
    }
}
