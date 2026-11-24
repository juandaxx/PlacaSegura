<?php

namespace Tests\Feature\Api;

use App\Domain\Contracts\Reloj;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Dobles\RelojFijo;
use Tests\TestCase;

class FranjaEndpointTest extends TestCase
{
    use RefreshDatabase;

    private int $diurnaId;

    private int $nocturnaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diurnaId = DB::table('franjas')->insertGetId(['nombre' => 'diurna', 'hora_inicio' => '06:00:00', 'hora_fin' => '18:00:00']);
        $this->nocturnaId = DB::table('franjas')->insertGetId(['nombre' => 'nocturna', 'hora_inicio' => '18:00:00', 'hora_fin' => '06:00:00']);
    }

    public function test_lista_las_franjas_con_sus_horarios(): void
    {
        $this->getJson('/api/franjas')
            ->assertOk()
            ->assertExactJson(['data' => [
                ['id' => $this->diurnaId, 'nombre' => 'diurna', 'hora_inicio' => '06:00', 'hora_fin' => '18:00'],
                ['id' => $this->nocturnaId, 'nombre' => 'nocturna', 'hora_inicio' => '18:00', 'hora_fin' => '06:00'],
            ]]);
    }

    public function test_cambiar_la_diurna_ajusta_la_nocturna(): void
    {
        $this->cambiar($this->diurnaId, '07:00', '18:00')
            ->assertOk()
            ->assertExactJson(['data' => [
                ['id' => $this->diurnaId, 'nombre' => 'diurna', 'hora_inicio' => '07:00', 'hora_fin' => '18:00'],
                ['id' => $this->nocturnaId, 'nombre' => 'nocturna', 'hora_inicio' => '18:00', 'hora_fin' => '07:00'],
            ]]);

        $this->assertDatabaseHas('franjas', ['id' => $this->diurnaId, 'hora_inicio' => '07:00:00', 'hora_fin' => '18:00:00']);
        $this->assertDatabaseHas('franjas', ['id' => $this->nocturnaId, 'hora_inicio' => '18:00:00', 'hora_fin' => '07:00:00']);
    }

    public function test_cambiar_la_nocturna_ajusta_la_diurna(): void
    {
        $this->cambiar($this->nocturnaId, '19:00', '05:00')
            ->assertOk()
            ->assertJsonPath('data.0.hora_inicio', '05:00')
            ->assertJsonPath('data.0.hora_fin', '19:00');
    }

    public function test_el_horario_nuevo_se_aplica_a_los_ingresos_siguientes(): void
    {
        $this->app->instance(Reloj::class, new RelojFijo('2026-11-20 06:30:00'));
        $sedeId = DB::table('sedes')->insertGetId(['nombre' => 'Sede Centro', 'direccion' => 'Calle 1 # 2-3']);
        $entradaId = DB::table('carriles')->insertGetId(['sede_id' => $sedeId, 'nombre' => 'Entrada 1', 'tipo' => 'entrada']);
        $carroId = DB::table('tipos_vehiculo')->insertGetId(['nombre' => 'carro', 'patron_placa' => '^[A-Z]{3}[0-9]{3}$']);
        DB::table('tarifas')->insert([
            ['sede_id' => $sedeId, 'tipo_vehiculo_id' => $carroId, 'franja_id' => $this->diurnaId, 'valor' => 5000],
            ['sede_id' => $sedeId, 'tipo_vehiculo_id' => $carroId, 'franja_id' => $this->nocturnaId, 'valor' => 3000],
        ]);

        $this->cambiar($this->diurnaId, '07:00', '18:00')->assertOk();

        // A las 06:30 ya no es diurna: aplica la tarifa nocturna.
        $this->postJson('/api/accesos/ingreso', [
            'placa' => 'ABC123', 'confianza' => 0.95, 'sede_id' => $sedeId, 'carril_id' => $entradaId,
        ])->assertOk()->assertJsonPath('cobro.valor', 3000);
    }

    public function test_rechaza_horarios_que_no_tienen_sentido(): void
    {
        $this->cambiar($this->diurnaId, '08:00', '08:00')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hora_fin']);

        $this->cambiar($this->diurnaId, '20:00', '08:00')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hora_inicio']);

        $this->cambiar($this->diurnaId, '7am', '25:00')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hora_inicio', 'hora_fin']);

        $this->assertDatabaseHas('franjas', ['id' => $this->diurnaId, 'hora_inicio' => '06:00:00', 'hora_fin' => '18:00:00']);
    }

    public function test_una_franja_inexistente_responde_404(): void
    {
        $this->cambiar(9999, '07:00', '18:00')->assertNotFound();
    }

    public function test_sin_una_de_las_dos_franjas_no_se_puede_ajustar(): void
    {
        DB::table('franjas')->where('id', $this->nocturnaId)->delete();

        $this->cambiar($this->diurnaId, '07:00', '18:00')->assertConflict();
    }

    public function test_no_permite_dos_franjas_con_el_mismo_nombre(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        DB::table('franjas')->insert(['nombre' => 'diurna', 'hora_inicio' => '05:00:00', 'hora_fin' => '06:00:00']);
    }

    private function cambiar(int $franjaId, string $horaInicio, string $horaFin): TestResponse
    {
        return $this->putJson("/api/franjas/{$franjaId}", ['hora_inicio' => $horaInicio, 'hora_fin' => $horaFin]);
    }
}
