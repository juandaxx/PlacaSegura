<?php

namespace App\Domain\Vehiculo;

use App\Domain\Contracts\TipoVehiculoRepository;

/**
 * F-02: clasifica el vehiculo por el formato de su placa
 * (por ejemplo, AAA123 es carro y AAA12A es moto).
 */
final class ClasificadorVehiculo
{
    public function __construct(private readonly TipoVehiculoRepository $tipos) {}

    public function clasificar(string $placa): ?TipoVehiculo
    {
        foreach ($this->tipos->todos() as $tipo) {
            if ($tipo->reconoce($placa)) {
                return $tipo;
            }
        }

        return null;
    }
}
