<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TarifaEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE_RN02 = 'La tarifa nocturna debe ser menor que la diurna.';

    private int $sedeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sedeId = DB::table('sedes')->insertGetId(['nombre' => 'Sede Centro', 'direccion' => 'Calle 1 # 2-3']);
        DB::table('tipos_vehiculo')->insert([
            ['nombre' => 'carro', 'patron_placa' => '^[A-Z]{3}[0-9]{3}$'],
            ['nombre' => 'moto', 'patron_placa' => '^[A-Z]{3}[0-9]{2}[A-Z]$'],
        ]);
        DB::table('franjas')->insert([
            ['nombre' => 'diurna', 'hora_inicio' => '06:00:00', 'hora_fin' => '18:00:00'],
            ['nombre' => 'nocturna', 'hora_inicio' => '18:00:00', 'hora_fin' => '06:00:00'],
        ]);
    }

    public function test_crea_una_tarifa_por_nombre_de_tipo_y_franja(): void
    {
        $this->crearTarifa('carro', 'diurna', 5000)
            ->assertCreated()
            ->assertJsonPath('data.sede_id', $this->sedeId)
            ->assertJsonPath('data.tipo_vehiculo', 'carro')
            ->assertJsonPath('data.franja', 'diurna')
            ->assertJsonPath('data.valor', 5000);
    }

    public function test_rechaza_una_nocturna_mayor_o_igual_a_la_diurna_con_error_de_validacion(): void
    {
        $this->crearTarifa('carro', 'diurna', 5000)->assertCreated();

        $this->crearTarifa('carro', 'nocturna', 6000)
            ->assertUnprocessable()
            ->assertJsonPath('message', self::MENSAJE_RN02)
            ->assertJsonValidationErrors(['valor' => self::MENSAJE_RN02]);
        $this->crearTarifa('carro', 'nocturna', 5000)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valor' => self::MENSAJE_RN02]);

        $this->assertDatabaseCount('tarifas', 1);
    }

    public function test_acepta_una_nocturna_menor_que_la_diurna(): void
    {
        $this->crearTarifa('carro', 'diurna', 5000)->assertCreated();

        $this->crearTarifa('carro', 'nocturna', 3000)
            ->assertCreated()
            ->assertJsonPath('data.franja', 'nocturna')
            ->assertJsonPath('data.valor', 3000);
    }

    public function test_permite_moto_en_cero_en_ambas_franjas(): void
    {
        $this->crearTarifa('moto', 'diurna', 0)->assertCreated();
        $this->crearTarifa('moto', 'nocturna', 0)->assertCreated();
    }

    public function test_no_permite_bajar_la_diurna_por_debajo_de_la_nocturna(): void
    {
        $diurnaId = $this->crearTarifa('carro', 'diurna', 5000)->json('data.id');
        $this->crearTarifa('carro', 'nocturna', 4000)->assertCreated();

        $this->putJson("/api/tarifas/{$diurnaId}", ['valor' => 3500])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valor' => self::MENSAJE_RN02]);

        $this->assertDatabaseHas('tarifas', ['id' => $diurnaId, 'valor' => 5000]);
    }

    public function test_modifica_el_valor_de_una_tarifa(): void
    {
        $id = $this->crearTarifa('carro', 'diurna', 5000)->json('data.id');

        $this->putJson("/api/tarifas/{$id}", ['valor' => 5500.5])
            ->assertOk()
            ->assertJsonPath('data.valor', 5500.5);
    }

    public function test_rechaza_una_tarifa_duplicada(): void
    {
        $this->crearTarifa('carro', 'diurna', 5000)->assertCreated();

        $this->crearTarifa('carro', 'diurna', 4500)->assertConflict();
    }

    public function test_valida_los_datos(): void
    {
        $this->postJson('/api/tarifas', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tipo_vehiculo', 'franja', 'valor']);

        $this->crearTarifa('bicicleta', 'tarde', -100)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tipo_vehiculo', 'franja', 'valor']);
    }

    public function test_con_varias_sedes_exige_sede_id(): void
    {
        $otraSedeId = DB::table('sedes')->insertGetId(['nombre' => 'Sede Norte', 'direccion' => 'Calle 100']);

        $this->crearTarifa('carro', 'diurna', 5000)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sede_id']);

        $this->crearTarifa('carro', 'diurna', 5000, ['sede_id' => $otraSedeId])
            ->assertCreated()
            ->assertJsonPath('data.sede_id', $otraSedeId);
    }

    public function test_lista_las_tarifas_paginadas(): void
    {
        $this->crearTarifa('carro', 'diurna', 5000)->assertCreated();
        $this->crearTarifa('carro', 'nocturna', 3000)->assertCreated();
        $this->crearTarifa('moto', 'diurna', 0)->assertCreated();

        $this->getJson('/api/tarifas?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.tipo_vehiculo', 'carro')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2);

        $this->getJson('/api/tarifas?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tipo_vehiculo', 'moto');

        $this->getJson('/api/tarifas?per_page=500')->assertUnprocessable();
    }

    public function test_modificar_una_tarifa_inexistente_responde_404(): void
    {
        $this->putJson('/api/tarifas/9999', ['valor' => 100])->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function crearTarifa(string $tipoVehiculo, string $franja, int|float $valor, array $extra = []): TestResponse
    {
        return $this->postJson('/api/tarifas', array_merge([
            'tipo_vehiculo' => $tipoVehiculo,
            'franja' => $franja,
            'valor' => $valor,
        ], $extra));
    }
}
