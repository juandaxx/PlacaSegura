<?php

namespace App\Domain\Tiempo;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Dia operativo del parqueadero (S-02): empieza a la hora de inicio (06:00)
 * y termina un segundo antes de esa hora del dia siguiente (05:59:59).
 *
 * Es la unica fuente de este calculo: ingreso, salida, cobros, pagos y
 * reportes deben usar esta clase en lugar de repetir la regla.
 */
final class DiaOperativo
{
    private readonly int $hora;

    private readonly int $minuto;

    public function __construct(string $horaInicio = '06:00')
    {
        if (! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $horaInicio, $partes)) {
            throw new InvalidArgumentException("Hora de inicio del dia operativo invalida: {$horaInicio}");
        }

        $this->hora = (int) $partes[1];
        $this->minuto = (int) $partes[2];
    }

    /**
     * Fecha del dia operativo al que pertenece un momento.
     * Ejemplo: 2026-11-21 03:00 pertenece al dia operativo 2026-11-20.
     */
    public function fechaDe(DateTimeImmutable $momento): DateTimeImmutable
    {
        $inicioDelDia = $momento->setTime($this->hora, $this->minuto);
        $fecha = $momento < $inicioDelDia ? $momento->modify('-1 day') : $momento;

        return $fecha->setTime(0, 0);
    }

    /**
     * Ultimo segundo del dia operativo que empieza en la fecha dada.
     * Ejemplo: 2026-11-20 termina el 2026-11-21 a las 05:59:59.
     */
    public function finDe(DateTimeImmutable $fecha): DateTimeImmutable
    {
        return $fecha
            ->setTime($this->hora, $this->minuto)
            ->modify('+1 day')
            ->modify('-1 second');
    }
}
