<?php

namespace Tests\Unit\Application;

use App\Application\UseCases\AnularCobro;
use App\Domain\Cobro\AnulacionRechazada;
use App\Domain\Cobro\EstadoCobro;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AnularCobroTest extends TestCase
{
    private CobroRepositoryEnMemoria $repositorio;

    private AnularCobro $anularCobro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositorio = new CobroRepositoryEnMemoria;
        $this->anularCobro = new AnularCobro($this->repositorio, new TransaccionInmediata);
    }

    public function test_anula_un_cobro_pendiente(): void
    {
        $this->repositorio->agregar(1);

        $cobro = $this->anularCobro->ejecutar(1);

        $this->assertSame(EstadoCobro::Anulado, $cobro->estado);
        $this->assertSame(EstadoCobro::Anulado, $this->repositorio->cobros[1]->estado);
    }

    public function test_anular_un_cobro_ya_anulado_lo_devuelve_sin_error(): void
    {
        $this->repositorio->agregar(1, EstadoCobro::Anulado);

        $this->assertSame(EstadoCobro::Anulado, $this->anularCobro->ejecutar(1)->estado);
    }

    #[DataProvider('estadosQueNoSeAnulan')]
    public function test_no_anula_un_cobro_que_no_esta_pendiente(EstadoCobro $estado): void
    {
        $this->repositorio->agregar(1, $estado);

        $this->assertRechazo(AnulacionRechazada::COBRO_NO_PENDIENTE, fn () => $this->anularCobro->ejecutar(1));
        $this->assertSame($estado, $this->repositorio->cobros[1]->estado);
    }

    public function test_rechaza_un_cobro_que_no_existe(): void
    {
        $this->assertRechazo(AnulacionRechazada::COBRO_NO_ENCONTRADO, fn () => $this->anularCobro->ejecutar(99));
    }

    /**
     * @return array<string, array{EstadoCobro}>
     */
    public static function estadosQueNoSeAnulan(): array
    {
        return [
            'pagado' => [EstadoCobro::Pagado],
            'vencido' => [EstadoCobro::Vencido],
        ];
    }

    private function assertRechazo(string $motivoEsperado, Closure $accion): void
    {
        try {
            $accion();
        } catch (AnulacionRechazada $rechazo) {
            $this->assertSame($motivoEsperado, $rechazo->motivo);

            return;
        }

        $this->fail("Se esperaba AnulacionRechazada con motivo {$motivoEsperado}.");
    }
}
