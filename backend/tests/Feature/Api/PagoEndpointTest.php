<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PagoEndpointTest extends TestCase
{
    use RefreshDatabase;

    private int $cobroId;

    private int $operadorId;

    protected function setUp(): void
    {
        parent::setUp();

        $sedeId = DB::table('sedes')->insertGetId(['nombre' => 'Sede Centro', 'direccion' => 'Calle 1 # 2-3']);
        $tipoId = DB::table('tipos_vehiculo')->insertGetId(['nombre' => 'carro', 'patron_placa' => '^[A-Z]{3}[0-9]{3}$']);
        $franjaId = DB::table('franjas')->insertGetId(['nombre' => 'diurna', 'hora_inicio' => '06:00:00', 'hora_fin' => '18:00:00']);
        $tarifaId = DB::table('tarifas')->insertGetId([
            'sede_id' => $sedeId, 'tipo_vehiculo_id' => $tipoId, 'franja_id' => $franjaId, 'valor' => 5000,
        ]);
        $carrilId = DB::table('carriles')->insertGetId(['sede_id' => $sedeId, 'nombre' => 'Entrada 1', 'tipo' => 'entrada']);
        $this->operadorId = DB::table('usuarios')->insertGetId([
            'nombre' => 'Operador Caja', 'email' => 'operador@placasegura.test',
            'password' => bcrypt('secret'), 'rol' => 'operador', 'activo' => true,
        ]);
        $eventoId = DB::table('eventos_acceso')->insertGetId([
            'carril_id' => $carrilId, 'tipo_vehiculo_id' => $tipoId, 'placa' => 'ABC123', 'tipo' => 'ingreso',
            'confianza' => 97.5, 'lectura_manual' => false, 'fecha_hora' => '2026-11-20 08:15:00',
        ]);
        $this->cobroId = DB::table('cobros')->insertGetId([
            'sede_id' => $sedeId, 'tarifa_id' => $tarifaId, 'evento_ingreso_id' => $eventoId, 'placa' => 'ABC123',
            'dia_operativo' => '2026-11-20', 'valor' => 5000, 'estado' => 'pendiente',
        ]);
    }

    public function test_registra_el_pago_y_marca_el_cobro_como_pagado(): void
    {
        $respuesta = $this->postJson('/api/pagos', $this->datosPago());

        $respuesta->assertCreated()
            ->assertJsonPath('data.cobro_id', $this->cobroId)
            ->assertJsonPath('data.valor', '5000.00')
            ->assertJsonPath('data.referencia', 'CAJA1-0001');
        $this->assertStringStartsWith('2026-11-21T05:59:59', $respuesta->json('data.pase_hasta'));
        $this->assertDatabaseHas('cobros', ['id' => $this->cobroId, 'estado' => 'pagado']);
    }

    public function test_repetir_la_misma_referencia_devuelve_el_mismo_pago_sin_duplicarlo(): void
    {
        $primero = $this->postJson('/api/pagos', $this->datosPago())->assertCreated();

        $this->postJson('/api/pagos', $this->datosPago())
            ->assertOk()
            ->assertJsonPath('data.id', $primero->json('data.id'));
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_un_cobro_ya_pagado_no_acepta_otra_referencia(): void
    {
        $this->postJson('/api/pagos', $this->datosPago())->assertCreated();

        $this->postJson('/api/pagos', $this->datosPago(['referencia' => 'CAJA1-0002']))
            ->assertConflict()
            ->assertJsonPath('motivo', 'cobro_no_pendiente');
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_un_cobro_inexistente_responde_404(): void
    {
        $this->postJson('/api/pagos', $this->datosPago(['cobro_id' => 9999]))
            ->assertNotFound()
            ->assertJsonPath('motivo', 'cobro_no_encontrado');
    }

    public function test_valida_los_datos_obligatorios(): void
    {
        $this->postJson('/api/pagos', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cobro_id', 'usuario_id', 'medio', 'referencia']);
    }

    public function test_lista_y_consulta_los_pagos(): void
    {
        $id = $this->postJson('/api/pagos', $this->datosPago())->json('data.id');

        $this->getJson("/api/pagos?cobro_id={$this->cobroId}")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/pagos/{$id}")->assertOk()->assertJsonPath('data.id', $id);
        $this->getJson('/api/pagos/9999')->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datosPago(array $cambios = []): array
    {
        return array_merge([
            'cobro_id' => $this->cobroId,
            'usuario_id' => $this->operadorId,
            'medio' => 'efectivo',
            'referencia' => 'CAJA1-0001',
        ], $cambios);
    }
}
