<?php

namespace Tests\Unit\Domain\Vehiculo;

use App\Domain\Contracts\TipoVehiculoRepository;
use App\Domain\Vehiculo\ClasificadorVehiculo;
use App\Domain\Vehiculo\TipoVehiculo;
use PHPUnit\Framework\TestCase;

class ClasificadorVehiculoTest extends TestCase
{
    public function test_clasifica_carro_y_moto_por_el_formato_de_la_placa(): void
    {
        $carro = new TipoVehiculo(1, 'carro', '^[A-Z]{3}[0-9]{3}$');
        $moto = new TipoVehiculo(2, 'moto', '^[A-Z]{3}[0-9]{2}[A-Z]$');
        $clasificador = $this->clasificador($carro, $moto);

        $this->assertSame($carro, $clasificador->clasificar('ABC123'));
        $this->assertSame($moto, $clasificador->clasificar('ABC12D'));
        $this->assertNull($clasificador->clasificar('AB1234'));
    }

    public function test_la_placa_debe_cumplir_el_patron_completo(): void
    {
        $clasificador = $this->clasificador(new TipoVehiculo(1, 'carro', '[A-Z]{3}[0-9]{3}'));

        $this->assertNull($clasificador->clasificar('ABC1234'));
        $this->assertNull($clasificador->clasificar('XABC123'));
    }

    public function test_un_patron_mal_configurado_no_reconoce_ninguna_placa(): void
    {
        $clasificador = $this->clasificador(new TipoVehiculo(1, 'roto', '[A-Z'));

        $this->assertNull($clasificador->clasificar('ABC123'));
    }

    private function clasificador(TipoVehiculo ...$tipos): ClasificadorVehiculo
    {
        return new ClasificadorVehiculo(new class($tipos) implements TipoVehiculoRepository
        {
            /**
             * @param  list<TipoVehiculo>  $tipos
             */
            public function __construct(private readonly array $tipos) {}

            public function todos(): array
            {
                return $this->tipos;
            }
        });
    }
}
