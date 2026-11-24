<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventoEndpointTest extends TestCase
{
    use RefreshDatabase;

    private int $sedeId;

    private int $entradaId;

    private int $salidaId;

    private int $carroId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sedeId = DB::table('sedes')->insertGetId(['nombre' => 'Sede Centro', 'direccion' => 'Calle 1 # 2-3']);
        $this->entradaId = DB::table('carriles')->insertGetId(['sede_id' => $this->sedeId, 'nombre' => 'Entrada 1', 'tipo' => 'entrada']);
        $this->salidaId = DB::table('carriles')->insertGetId(['sede_id' => $this->sedeId, 'nombre' => 'Salida 1', 'tipo' => 'salida']);
        $this->carroId = DB::table('tipos_vehiculo')->insertGetId(['nombre' => 'carro', 'patron_placa' => '^[A-Z]{3}[0-9]{3}$']);
    }

    public function test_lista_los_eventos_paginados_del_mas_reciente_al_mas_antiguo(): void
    {
        $antiguo = $this->evento('ABC123', 'ingreso', '2026-11-20 08:00:00');
        $this->evento('ABC123', 'salida', '2026-11-20 12:00:00');
        $reciente = $this->evento('DEF456', 'ingreso', '2026-11-20 15:00:00');

        $this->getJson('/api/eventos?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $reciente)
            ->assertJsonPath('meta.total', 3);

        $this->getJson('/api/eventos?per_page=2&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.id', $antiguo);
    }

    public function test_el_detalle_muestra_la_decision_del_carril(): void
    {
        $id = $this->evento('ABC123', 'ingreso', '2026-11-20 08:15:00');

        $this->getJson("/api/eventos/{$id}")
            ->assertOk()
            ->assertExactJson(['data' => [
                'id' => $id,
                'sede_id' => $this->sedeId,
                'carril_id' => $this->entradaId,
                'placa' => 'ABC123',
                'tipo' => 'ingreso',
                'tipo_vehiculo' => 'carro',
                'fecha_hora' => '2026-11-20T08:15:00-05:00',
                'confianza' => 0.95,
                'lectura_manual' => false,
                'usuario_id' => null,
                'motivo' => 'OK',
                'barrera' => 'abierta',
            ]]);
    }

    public function test_incluye_las_lecturas_en_las_que_la_barrera_no_abrio(): void
    {
        $id = $this->evento('DEF456', 'ingreso', '2026-11-20 09:00:00', 'SIN_CUPO');

        $this->getJson("/api/eventos/{$id}")
            ->assertJsonPath('data.motivo', 'SIN_CUPO')
            ->assertJsonPath('data.barrera', 'cerrada');
    }

    public function test_filtra_por_placa_tipo_y_dia_operativo(): void
    {
        $this->evento('ABC123', 'ingreso', '2026-11-20 08:00:00');
        $this->evento('ABC123', 'salida', '2026-11-20 18:00:00');
        $deMadrugada = $this->evento('DEF456', 'ingreso', '2026-11-21 02:00:00');
        $this->evento('DEF456', 'salida', '2026-11-21 07:00:00');

        $this->getJson('/api/eventos?placa='.urlencode('abc 123'))->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/eventos?tipo=salida')->assertOk()->assertJsonCount(2, 'data');

        // Las 02:00 del 21 todavia son el dia operativo del 20; las 07:00 ya no.
        $this->getJson('/api/eventos?fecha=2026-11-20')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/eventos?fecha=2026-11-20&placa=DEF456')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $deMadrugada);
    }

    public function test_valida_los_filtros(): void
    {
        $this->getJson('/api/eventos?tipo=entrada')->assertUnprocessable()->assertJsonValidationErrors(['tipo']);
        $this->getJson('/api/eventos?fecha=20-11-2026')->assertUnprocessable()->assertJsonValidationErrors(['fecha']);
    }

    public function test_un_evento_inexistente_responde_404(): void
    {
        $this->getJson('/api/eventos/9999')->assertNotFound();
    }

    private function evento(string $placa, string $tipo, string $fechaHora, string $motivo = 'OK'): int
    {
        return DB::table('eventos_acceso')->insertGetId([
            'carril_id' => $tipo === 'ingreso' ? $this->entradaId : $this->salidaId,
            'tipo_vehiculo_id' => $this->carroId,
            'placa' => $placa,
            'tipo' => $tipo,
            'confianza' => 0.95,
            'lectura_manual' => false,
            'fecha_hora' => $fechaHora,
            'motivo' => $motivo,
        ]);
    }
}
