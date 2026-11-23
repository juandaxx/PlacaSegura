<?php

namespace Tests\Unit\Domain\Tarifa;

use App\Domain\Tarifa\Franja;
use App\Domain\Tarifa\NombreFranja;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class FranjaTest extends TestCase
{
    public function test_la_franja_diurna_incluye_el_inicio_y_excluye_el_fin(): void
    {
        $diurna = new Franja(1, NombreFranja::Diurna, '06:00:00', '18:00:00');

        $this->assertTrue($diurna->contiene(new DateTimeImmutable('2026-11-20 06:00:00')));
        $this->assertTrue($diurna->contiene(new DateTimeImmutable('2026-11-20 17:59:59')));
        $this->assertFalse($diurna->contiene(new DateTimeImmutable('2026-11-20 18:00:00')));
        $this->assertFalse($diurna->contiene(new DateTimeImmutable('2026-11-20 05:59:59')));
    }

    public function test_la_franja_nocturna_cruza_la_medianoche(): void
    {
        $nocturna = new Franja(2, NombreFranja::Nocturna, '18:00', '06:00');

        $this->assertTrue($nocturna->contiene(new DateTimeImmutable('2026-11-20 18:00:00')));
        $this->assertTrue($nocturna->contiene(new DateTimeImmutable('2026-11-20 23:30:00')));
        $this->assertTrue($nocturna->contiene(new DateTimeImmutable('2026-11-21 05:59:59')));
        $this->assertFalse($nocturna->contiene(new DateTimeImmutable('2026-11-21 06:00:00')));
        $this->assertFalse($nocturna->contiene(new DateTimeImmutable('2026-11-21 12:00:00')));
    }
}
