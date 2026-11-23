<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CobroEndpointTest extends TestCase
{
    use RefreshDatabase;

    private int $sedeId;

    private int $carroId;

    private int $tarifaId;

    private int $carrilId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sedeId = DB::table('sedes')->insertGetId(['nombre' => 'Sede Centro', 'direccion' => 'Calle 1 # 2-3']);
        $this->carroId = DB::table('tipos_vehiculo')->insertGetId(['nombre' => 'carro', 'patron_placa' => '^[A-Z]{3}[0-9]{3}$']);
        $diurnaId = DB::table('franjas')->insertGetId(['nombre' => 'diurna', 'hora_inicio' => '06:00:00', 'hora_fin' => '18:00:00']);
        $this->tarifaId = DB::table('tarifas')->insertGetId([
            'sede_id' => $this->sedeId, 'tipo_vehiculo_id' => $this->carroId, 'franja_id' => $diurnaId, 'valor' => 5000,
        ]);
        $this->carrilId = DB::table('carriles')->insertGetId(['sede_id' => $this->sedeId, 'nombre' => 'Entrada 1', 'tipo' => 'entrada']);
    }

    public function test_lista_los_cobros_paginados_del_mas_reciente_al_mas_antiguo(): void
    {
        $antiguo = $this->crearCobro('ABC123', '2026-11-19');
        $this->crearCobro('XYZ987', '2026-11-20');
        $reciente = $this->crearCobro('DEF456', '2026-11-21');

        $this->getJson('/api/cobros?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $reciente)
            ->assertJsonPath('meta.total', 3);

        $this->getJson('/api/cobros?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $antiguo);
    }

    public function test_filtra_por_placa_fecha_y_estado(): void
    {
        $this->crearCobro('ABC123', '2026-11-20', 'pagado');
        $buscado = $this->crearCobro('ABC123', '2026-11-21');
        $this->crearCobro('XYZ987', '2026-11-21');

        $this->getJson('/api/cobros?placa=ABC123')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/cobros?fecha=2026-11-21')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/cobros?estado=pagado')->assertOk()->assertJsonCount(1, 'data');

        $this->getJson('/api/cobros?placa=ABC123&fecha=2026-11-21&estado=pendiente')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $buscado);
    }

    public function test_la_placa_se_busca_exacta_en_mayusculas_y_sin_espacios(): void
    {
        $this->crearCobro('ABC123', '2026-11-20');

        $this->getJson('/api/cobros?placa='.urlencode('abc 123'))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/cobros?placa=ABC12')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_valida_los_filtros(): void
    {
        $this->getJson('/api/cobros?estado=perdido')->assertUnprocessable()->assertJsonValidationErrors(['estado']);
        $this->getJson('/api/cobros?fecha=20-11-2026')->assertUnprocessable()->assertJsonValidationErrors(['fecha']);
        $this->getJson('/api/cobros?per_page=500')->assertUnprocessable()->assertJsonValidationErrors(['per_page']);
    }

    public function test_el_detalle_incluye_la_tarifa_con_la_que_se_calculo(): void
    {
        $id = $this->crearCobro('ABC123', '2026-11-20');

        $this->getJson("/api/cobros/{$id}")
            ->assertOk()
            ->assertJsonPath('data.placa', 'ABC123')
            ->assertJsonPath('data.sede_id', $this->sedeId)
            ->assertJsonPath('data.dia_operativo', '2026-11-20')
            ->assertJsonPath('data.valor', 5000)
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.tarifa.id', $this->tarifaId)
            ->assertJsonPath('data.tarifa.tipo_vehiculo', 'carro')
            ->assertJsonPath('data.tarifa.franja', 'diurna');

        $this->getJson('/api/cobros/9999')->assertNotFound();
    }

    public function test_anula_un_cobro_pendiente(): void
    {
        $id = $this->crearCobro('ABC123', '2026-11-20');

        $this->patchJson("/api/cobros/{$id}/anular")
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.estado', 'anulado');

        $this->assertDatabaseHas('cobros', ['id' => $id, 'estado' => 'anulado']);
    }

    public function test_anular_dos_veces_el_mismo_cobro_no_falla(): void
    {
        $id = $this->crearCobro('ABC123', '2026-11-20');

        $this->patchJson("/api/cobros/{$id}/anular")->assertOk();
        $this->patchJson("/api/cobros/{$id}/anular")->assertOk()->assertJsonPath('data.estado', 'anulado');
    }

    public function test_no_anula_un_cobro_pagado(): void
    {
        $id = $this->crearCobro('ABC123', '2026-11-20', 'pagado');

        $this->patchJson("/api/cobros/{$id}/anular")->assertConflict();

        $this->assertDatabaseHas('cobros', ['id' => $id, 'estado' => 'pagado']);
    }

    public function test_anular_un_cobro_inexistente_responde_404(): void
    {
        $this->patchJson('/api/cobros/9999/anular')->assertNotFound();
    }

    private function crearCobro(string $placa, string $diaOperativo, string $estado = 'pendiente'): int
    {
        $eventoId = DB::table('eventos_acceso')->insertGetId([
            'carril_id' => $this->carrilId,
            'tipo_vehiculo_id' => $this->carroId,
            'placa' => $placa,
            'tipo' => 'ingreso',
            'confianza' => 95,
            'lectura_manual' => false,
            'fecha_hora' => "{$diaOperativo} 08:00:00",
        ]);

        return DB::table('cobros')->insertGetId([
            'sede_id' => $this->sedeId,
            'tarifa_id' => $this->tarifaId,
            'evento_ingreso_id' => $eventoId,
            'placa' => $placa,
            'dia_operativo' => $diaOperativo,
            'valor' => 5000,
            'estado' => $estado,
        ]);
    }
}
