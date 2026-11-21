<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TarifaEndpointTest extends TestCase
{
    use RefreshDatabase;

    private int $sedeId;

    private int $carroId;

    private int $motoId;

    private int $diurnaId;

    private int $nocturnaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sedeId = DB::table('sedes')->insertGetId(['nombre' => 'Sede Centro', 'direccion' => 'Calle 1 # 2-3']);
        $this->carroId = DB::table('tipos_vehiculo')->insertGetId(['nombre' => 'carro', 'patron_placa' => '^[A-Z]{3}[0-9]{3}$']);
        $this->motoId = DB::table('tipos_vehiculo')->insertGetId(['nombre' => 'moto', 'patron_placa' => '^[A-Z]{3}[0-9]{2}[A-Z]$']);
        $this->diurnaId = DB::table('franjas')->insertGetId(['nombre' => 'diurna', 'hora_inicio' => '06:00:00', 'hora_fin' => '18:00:00']);
        $this->nocturnaId = DB::table('franjas')->insertGetId(['nombre' => 'nocturna', 'hora_inicio' => '18:00:00', 'hora_fin' => '06:00:00']);
    }

    public function test_crea_una_tarifa(): void
    {
        $this->crearTarifa($this->carroId, $this->diurnaId, 5000)
            ->assertCreated()
            ->assertJsonPath('data.sede_id', $this->sedeId)
            ->assertJsonPath('data.franja', 'diurna')
            ->assertJsonPath('data.valor', '5000.00');
    }

    public function test_rechaza_una_nocturna_mayor_o_igual_a_la_diurna(): void
    {
        $this->crearTarifa($this->carroId, $this->diurnaId, 5000)->assertCreated();

        $this->crearTarifa($this->carroId, $this->nocturnaId, 6000)
            ->assertUnprocessable()
            ->assertJsonPath('motivo', 'nocturna_no_menor_que_diurna');
        $this->crearTarifa($this->carroId, $this->nocturnaId, 5000)
            ->assertUnprocessable()
            ->assertJsonPath('motivo', 'nocturna_no_menor_que_diurna');

        $this->assertDatabaseCount('tarifas', 1);
    }

    public function test_acepta_una_nocturna_menor_que_la_diurna(): void
    {
        $this->crearTarifa($this->carroId, $this->diurnaId, 5000)->assertCreated();

        $this->crearTarifa($this->carroId, $this->nocturnaId, 4000)
            ->assertCreated()
            ->assertJsonPath('data.franja', 'nocturna');
    }

    public function test_permite_moto_en_cero_en_ambas_franjas(): void
    {
        $this->crearTarifa($this->motoId, $this->diurnaId, 0)->assertCreated();
        $this->crearTarifa($this->motoId, $this->nocturnaId, 0)->assertCreated();
    }

    public function test_no_permite_bajar_la_diurna_por_debajo_de_la_nocturna(): void
    {
        $diurnaId = $this->crearTarifa($this->carroId, $this->diurnaId, 5000)->json('data.id');
        $this->crearTarifa($this->carroId, $this->nocturnaId, 4000)->assertCreated();

        $this->putJson("/api/tarifas/{$diurnaId}", ['valor' => 3500])
            ->assertUnprocessable()
            ->assertJsonPath('motivo', 'nocturna_no_menor_que_diurna');

        $this->assertDatabaseHas('tarifas', ['id' => $diurnaId, 'valor' => 5000]);
    }

    public function test_actualiza_el_valor_de_una_tarifa(): void
    {
        $id = $this->crearTarifa($this->carroId, $this->diurnaId, 5000)->json('data.id');

        $this->putJson("/api/tarifas/{$id}", ['valor' => 5500])
            ->assertOk()
            ->assertJsonPath('data.valor', '5500.00');
    }

    public function test_rechaza_una_tarifa_duplicada(): void
    {
        $this->crearTarifa($this->carroId, $this->diurnaId, 5000)->assertCreated();

        $this->crearTarifa($this->carroId, $this->diurnaId, 4500)
            ->assertConflict()
            ->assertJsonPath('motivo', 'tarifa_ya_existe');
    }

    public function test_valida_los_datos(): void
    {
        $this->postJson('/api/tarifas', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sede_id', 'tipo_vehiculo_id', 'franja_id', 'valor']);

        $this->crearTarifa($this->carroId, $this->diurnaId, -100)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valor']);
    }

    public function test_lista_y_consulta_las_tarifas(): void
    {
        $id = $this->crearTarifa($this->carroId, $this->diurnaId, 5000)->json('data.id');
        $this->crearTarifa($this->motoId, $this->diurnaId, 0)->assertCreated();

        $this->getJson("/api/tarifas?tipo_vehiculo_id={$this->carroId}")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/tarifas?sede_id={$this->sedeId}")->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/tarifas/{$id}")->assertOk()->assertJsonPath('data.id', $id);
        $this->getJson('/api/tarifas/9999')->assertNotFound();
        $this->putJson('/api/tarifas/9999', ['valor' => 100])->assertNotFound();
    }

    private function crearTarifa(int $tipoVehiculoId, int $franjaId, int|float $valor): TestResponse
    {
        return $this->postJson('/api/tarifas', [
            'sede_id' => $this->sedeId,
            'tipo_vehiculo_id' => $tipoVehiculoId,
            'franja_id' => $franjaId,
            'valor' => $valor,
        ]);
    }
}
