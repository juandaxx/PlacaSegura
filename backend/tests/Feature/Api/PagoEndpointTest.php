<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PagoEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const REFERENCIA = 'CAJA1-20261120-0007';

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

    public function test_registra_el_pago_con_el_formato_del_documento(): void
    {
        $respuesta = $this->pagar()->assertCreated();

        $respuesta->assertExactJson([
            'pago_id' => (int) DB::table('pagos')->value('id'),
            'cobro_id' => $this->cobroId,
            'estado_cobro' => 'pagado',
            'pase_vigente_hasta' => '2026-11-21T05:59:59-05:00',
        ]);
        $this->assertDatabaseHas('cobros', ['id' => $this->cobroId, 'estado' => 'pagado']);
        $this->assertDatabaseHas('pagos', [
            'cobro_id' => $this->cobroId,
            'usuario_id' => $this->operadorId,
            'medio' => 'efectivo',
            'referencia' => self::REFERENCIA,
        ]);
    }

    public function test_la_misma_referencia_devuelve_el_pago_ya_registrado_con_200(): void
    {
        $primero = $this->pagar()->assertCreated();

        $this->pagar()
            ->assertOk()
            ->assertJsonPath('pago_id', $primero->json('pago_id'))
            ->assertJsonPath('estado_cobro', 'pagado');

        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_un_cobro_anulado_responde_409(): void
    {
        DB::table('cobros')->where('id', $this->cobroId)->update(['estado' => 'anulado']);

        $this->pagar()->assertConflict()->assertJsonStructure(['message']);

        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_un_cobro_ya_pagado_no_acepta_otra_referencia(): void
    {
        $this->pagar()->assertCreated();

        $this->pagar(['referencia' => 'CAJA1-20261120-0008'])->assertConflict();

        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_un_cobro_inexistente_responde_404(): void
    {
        $this->postJson('/api/cobros/9999/pagos', $this->datosPago())->assertNotFound();
    }

    public function test_valida_los_datos(): void
    {
        $this->postJson("/api/cobros/{$this->cobroId}/pagos", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['medio', 'referencia', 'usuario_id']);

        $this->pagar(['medio' => 'tarjeta'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['medio']);
    }

    public function test_con_sesion_iniciada_el_operador_es_el_usuario_autenticado(): void
    {
        $this->actingAs(User::findOrFail($this->operadorId))
            ->postJson("/api/cobros/{$this->cobroId}/pagos", ['medio' => 'efectivo', 'referencia' => self::REFERENCIA])
            ->assertCreated();

        $this->assertDatabaseHas('pagos', ['referencia' => self::REFERENCIA, 'usuario_id' => $this->operadorId]);
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function pagar(array $cambios = []): TestResponse
    {
        return $this->postJson("/api/cobros/{$this->cobroId}/pagos", $this->datosPago($cambios));
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datosPago(array $cambios = []): array
    {
        return array_merge([
            'medio' => 'efectivo',
            'referencia' => self::REFERENCIA,
            // Mientras no exista el login, el operador se envia en la peticion.
            'usuario_id' => $this->operadorId,
        ], $cambios);
    }
}
