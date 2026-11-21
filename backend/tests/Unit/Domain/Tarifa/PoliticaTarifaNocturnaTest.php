<?php

namespace Tests\Unit\Domain\Tarifa;

use App\Domain\Tarifa\NombreFranja;
use App\Domain\Tarifa\PoliticaTarifaNocturna;
use App\Domain\Tarifa\TarifaRechazada;
use PHPUnit\Framework\TestCase;

class PoliticaTarifaNocturnaTest extends TestCase
{
    private PoliticaTarifaNocturna $politica;

    protected function setUp(): void
    {
        parent::setUp();

        $this->politica = new PoliticaTarifaNocturna;
    }

    public function test_rechaza_una_nocturna_mayor_que_la_diurna(): void
    {
        $this->expectException(TarifaRechazada::class);

        $this->politica->validar(NombreFranja::Nocturna, '6000.00', ['5000.00']);
    }

    public function test_rechaza_una_nocturna_igual_a_la_diurna(): void
    {
        $this->expectException(TarifaRechazada::class);

        $this->politica->validar(NombreFranja::Nocturna, '5000', ['5000.00']);
    }

    public function test_rechaza_una_diurna_menor_o_igual_que_la_nocturna_existente(): void
    {
        $this->expectException(TarifaRechazada::class);

        $this->politica->validar(NombreFranja::Diurna, '3000.00', ['4000.00']);
    }

    public function test_acepta_una_nocturna_menor_que_la_diurna(): void
    {
        $this->politica->validar(NombreFranja::Nocturna, '4999.99', ['5000.00']);

        $this->addToAssertionCount(1);
    }

    public function test_acepta_cualquier_valor_si_la_otra_franja_no_esta_configurada(): void
    {
        $this->politica->validar(NombreFranja::Nocturna, '9000.00', []);

        $this->addToAssertionCount(1);
    }

    public function test_acepta_ambas_franjas_en_cero_para_vehiculos_gratuitos(): void
    {
        $this->politica->validar(NombreFranja::Nocturna, '0', ['0.00']);

        $this->addToAssertionCount(1);
    }

    public function test_el_rechazo_indica_el_motivo_rn02(): void
    {
        try {
            $this->politica->validar(NombreFranja::Nocturna, '6000.00', ['5000.00']);
            $this->fail('Se esperaba TarifaRechazada.');
        } catch (TarifaRechazada $rechazo) {
            $this->assertSame(TarifaRechazada::NOCTURNA_NO_MENOR_QUE_DIURNA, $rechazo->motivo);
        }
    }
}
