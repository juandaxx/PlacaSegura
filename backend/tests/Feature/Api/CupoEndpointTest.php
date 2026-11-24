<?php

namespace Tests\Feature\Api;

use App\Domain\Contracts\Reloj;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Dobles\RelojFijo;
use Tests\TestCase;

class CupoEndpointTest extends TestCase
{
    use RefreshDatabase;

    private int $sedeId;

    private int $entradaId;

    private int $motoId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(Reloj::class, new RelojFijo('2026-11-20 08:00:00'));

        $this->sedeId = DB::table('sedes')->insertGetId(['nombre' => 'Sede Centro', 'direccion' => 'Calle 1 # 2-3']);
        $this->entradaId = DB::table('carriles')->insertGetId(['sede_id' => $this->sedeId, 'nombre' => 'Entrada 1', 'tipo' => 'entrada']);

        $carroId = DB::table('tipos_vehiculo')->insertGetId(['nombre' => 'carro', 'patron_placa' => '^[A-Z]{3}[0-9]{3}$']);
        $this->motoId = DB::table('tipos_vehiculo')->insertGetId(['nombre' => 'moto', 'patron_placa' => '^[A-Z]{3}[0-9]{2}[A-Z]$']);
        DB::table('tipos_vehiculo')->insert(['nombre' => 'bicicleta', 'patron_placa' => '^BIC[0-9]{3}$']);

        $diurnaId = DB::table('franjas')->insertGetId(['nombre' => 'diurna', 'hora_inicio' => '06:00:00', 'hora_fin' => '18:00:00']);
        DB::table('tarifas')->insert([
            ['sede_id' => $this->sedeId, 'tipo_vehiculo_id' => $carroId, 'franja_id' => $diurnaId, 'valor' => 5000],
            ['sede_id' => $this->sedeId, 'tipo_vehiculo_id' => $this->motoId, 'franja_id' => $diurnaId, 'valor' => 0],
        ]);
        DB::table('cupos')->insert([
            ['sede_id' => $this->sedeId, 'tipo_vehiculo_id' => $carroId, 'total' => 2],
            ['sede_id' => $this->sedeId, 'tipo_vehiculo_id' => $this->motoId, 'total' => 1],
        ]);
        DB::table('configuracion')->insert(['clave' => 'umbral_confianza', 'valor' => '0.80']);
    }

    public function test_muestra_el_cupo_de_cada_tipo_de_vehiculo(): void
    {
        $this->getJson("/api/sedes/{$this->sedeId}/cupos")
            ->assertOk()
            ->assertExactJson([
                'sede_id' => $this->sedeId,
                'cupos' => [
                    ['tipo_vehiculo' => 'carro', 'total' => 2, 'ocupados' => 0, 'disponibles' => 2],
                    ['tipo_vehiculo' => 'moto', 'total' => 1, 'ocupados' => 0, 'disponibles' => 1],
                    // Sin cupo configurado: sin limite.
                    ['tipo_vehiculo' => 'bicicleta', 'total' => null, 'ocupados' => 0, 'disponibles' => null],
                ],
            ]);
    }

    public function test_la_ocupacion_coincide_con_la_decision_del_carril(): void
    {
        $this->ingreso('ABC123')->assertJsonPath('motivo', 'OK');
        $this->ingreso('DEF456')->assertJsonPath('motivo', 'OK');
        $this->ingreso('GHI789')->assertJsonPath('motivo', 'SIN_CUPO');

        // El intento rechazado no cuenta como vehiculo adentro.
        $this->getJson("/api/sedes/{$this->sedeId}/cupos")
            ->assertOk()
            ->assertJsonPath('cupos.0.ocupados', 2)
            ->assertJsonPath('cupos.0.disponibles', 0);
    }

    public function test_una_lectura_pendiente_de_validacion_no_ocupa_cupo(): void
    {
        $this->ingreso('XYZ12A', confianza: 0.3)->assertJsonPath('motivo', 'LECTURA_MANUAL');

        $this->getJson("/api/sedes/{$this->sedeId}/cupos")->assertJsonPath('cupos.1.ocupados', 0);
    }

    public function test_define_los_cupos_y_conserva_los_tipos_que_no_se_envian(): void
    {
        $this->definir([['tipo_vehiculo' => 'carro', 'total' => 50], ['tipo_vehiculo' => 'bicicleta', 'total' => 10]])
            ->assertOk()
            ->assertJsonPath('cupos.0.total', 50)
            ->assertJsonPath('cupos.1.total', 1)
            ->assertJsonPath('cupos.2.total', 10);

        $this->assertDatabaseHas('cupos', ['sede_id' => $this->sedeId, 'tipo_vehiculo_id' => $this->motoId, 'total' => 1]);
        $this->assertDatabaseCount('cupos', 3);
    }

    public function test_permite_un_cupo_menor_a_los_vehiculos_que_estan_adentro(): void
    {
        $this->ingreso('ABC123');
        $this->ingreso('DEF456');

        $this->definir([['tipo_vehiculo' => 'carro', 'total' => 1]])
            ->assertOk()
            ->assertJsonPath('cupos.0.ocupados', 2)
            ->assertJsonPath('cupos.0.disponibles', 0);
    }

    public function test_valida_los_cupos(): void
    {
        $this->putJson("/api/sedes/{$this->sedeId}/cupos", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cupos']);

        $this->definir([['tipo_vehiculo' => 'camion', 'total' => 5]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cupos.0.tipo_vehiculo']);

        $this->definir([['tipo_vehiculo' => 'carro', 'total' => 5], ['tipo_vehiculo' => 'carro', 'total' => 8]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cupos.0.tipo_vehiculo']);

        $this->definir([['tipo_vehiculo' => 'carro', 'total' => -1]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cupos.0.total']);
    }

    public function test_una_sede_inexistente_responde_404(): void
    {
        $this->getJson('/api/sedes/9999/cupos')->assertNotFound();
        $this->putJson('/api/sedes/9999/cupos', ['cupos' => [['tipo_vehiculo' => 'carro', 'total' => 5]]])->assertNotFound();
    }

    private function ingreso(string $placa, float $confianza = 0.95): TestResponse
    {
        return $this->postJson('/api/accesos/ingreso', [
            'placa' => $placa, 'confianza' => $confianza, 'sede_id' => $this->sedeId, 'carril_id' => $this->entradaId,
        ]);
    }

    /**
     * @param  list<array{tipo_vehiculo: string, total: int}>  $cupos
     */
    private function definir(array $cupos): TestResponse
    {
        return $this->putJson("/api/sedes/{$this->sedeId}/cupos", ['cupos' => $cupos]);
    }
}
