<?php

namespace Tests\Unit\Domain\Tarifa;

use App\Domain\Tarifa\Franja;
use App\Domain\Tarifa\FranjaRechazada;
use App\Domain\Tarifa\NombreFranja;
use App\Domain\Tarifa\PoliticaHorarioFranjas;
use Closure;
use PHPUnit\Framework\TestCase;

class PoliticaHorarioFranjasTest extends TestCase
{
    private const DIURNA = 1;

    private const NOCTURNA = 2;

    public function test_cambiar_la_diurna_ajusta_la_nocturna(): void
    {
        [$diurna, $nocturna] = $this->cambiar(self::DIURNA, '07:00', '18:00');

        $this->assertHorario('07:00:00', '18:00:00', $diurna);
        $this->assertHorario('18:00:00', '07:00:00', $nocturna);
    }

    public function test_cambiar_la_nocturna_ajusta_la_diurna(): void
    {
        [$diurna, $nocturna] = $this->cambiar(self::NOCTURNA, '19:00', '05:00');

        $this->assertHorario('05:00:00', '19:00:00', $diurna);
        $this->assertHorario('19:00:00', '05:00:00', $nocturna);
    }

    public function test_rechaza_un_horario_sin_duracion(): void
    {
        $this->assertRechazo(FranjaRechazada::SIN_DURACION, fn () => $this->cambiar(self::DIURNA, '08:00', '08:00'));
    }

    public function test_rechaza_una_diurna_que_cruza_la_medianoche(): void
    {
        $this->assertRechazo(
            FranjaRechazada::DIURNA_CRUZA_MEDIANOCHE,
            fn () => $this->cambiar(self::DIURNA, '20:00', '08:00'),
        );
    }

    public function test_rechaza_una_nocturna_que_no_cruza_la_medianoche(): void
    {
        // La diurna quedaria de 05:00 a 01:00, cruzando la medianoche.
        $this->assertRechazo(
            FranjaRechazada::DIURNA_CRUZA_MEDIANOCHE,
            fn () => $this->cambiar(self::NOCTURNA, '01:00', '05:00'),
        );
    }

    public function test_rechaza_una_franja_que_no_existe(): void
    {
        $this->assertRechazo(FranjaRechazada::NO_ENCONTRADA, fn () => $this->cambiar(99, '07:00', '18:00'));
    }

    public function test_exige_una_franja_diurna_y_una_nocturna(): void
    {
        $soloDiurna = [new Franja(self::DIURNA, NombreFranja::Diurna, '06:00:00', '18:00:00')];

        $this->assertRechazo(
            FranjaRechazada::CONFIGURACION_INVALIDA,
            fn () => (new PoliticaHorarioFranjas)->cambiarHorario($soloDiurna, self::DIURNA, '07:00', '18:00'),
        );
    }

    /**
     * @return list<Franja>
     */
    private function cambiar(int $franjaId, string $horaInicio, string $horaFin): array
    {
        $franjas = [
            new Franja(self::DIURNA, NombreFranja::Diurna, '06:00:00', '18:00:00'),
            new Franja(self::NOCTURNA, NombreFranja::Nocturna, '18:00:00', '06:00:00'),
        ];

        return (new PoliticaHorarioFranjas)->cambiarHorario($franjas, $franjaId, $horaInicio, $horaFin);
    }

    private function assertHorario(string $inicio, string $fin, Franja $franja): void
    {
        $this->assertSame([$inicio, $fin], [$franja->horaInicio, $franja->horaFin]);
    }

    private function assertRechazo(string $motivoEsperado, Closure $accion): void
    {
        try {
            $accion();
        } catch (FranjaRechazada $rechazo) {
            $this->assertSame($motivoEsperado, $rechazo->motivo);

            return;
        }

        $this->fail("Se esperaba FranjaRechazada con motivo {$motivoEsperado}.");
    }
}
