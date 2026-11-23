<?php

namespace App\Domain\Vehiculo;

/**
 * Tipo de vehiculo (carro, moto...) con el formato de placa que lo identifica.
 * Agregar un tipo nuevo es agregar un registro, sin cambiar codigo (F-02).
 */
final class TipoVehiculo
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre,
        public readonly string $patronPlaca,
    ) {}

    /**
     * La placa debe cumplir el patron completo. Un patron mal configurado no
     * reconoce ninguna placa en lugar de detener el carril con un error.
     */
    public function reconoce(string $placa): bool
    {
        $expresion = '/^(?:'.str_replace('/', '\/', $this->patronPlaca).')$/';

        // Si el patron no compila, preg_match emite una advertencia y devuelve
        // false; se descarta la advertencia y se trata como "no reconoce".
        set_error_handler(static fn (): bool => true);

        try {
            return preg_match($expresion, $placa) === 1;
        } finally {
            restore_error_handler();
        }
    }
}
