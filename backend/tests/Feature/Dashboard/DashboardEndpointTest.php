<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardEndpointTest extends TestCase
{
    use RefreshDatabase;

    private int $sedeId;

    private int $tipoId;

    private int $franjaId;

    private int $tarifaId;

    private int $carrilEntradaId;

    private int $carrilSalidaId;

    private User $operador;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sedeId = DB::table('sedes')->insertGetId(['nombre' => 'Sede Centro', 'direccion' => 'Calle 1']);
        $this->tipoId = DB::table('tipos_vehiculo')->insertGetId(['nombre' => 'carro', 'patron_placa' => '^[A-Z]{3}[0-9]{3}$']);
        $this->franjaId = DB::table('franjas')->insertGetId(['nombre' => 'diurna', 'hora_inicio' => '06:00:00', 'hora_fin' => '18:00:00']);
        $this->tarifaId = DB::table('tarifas')->insertGetId([
            'sede_id' => $this->sedeId,
            'tipo_vehiculo_id' => $this->tipoId,
            'franja_id' => $this->franjaId,
            'valor' => 5000,
        ]);
        DB::table('cupos')->insert(['sede_id' => $this->sedeId, 'tipo_vehiculo_id' => $this->tipoId, 'total' => 1]);
        $this->carrilEntradaId = DB::table('carriles')->insertGetId([
            'sede_id' => $this->sedeId,
            'nombre' => 'Entrada 1',
            'tipo' => 'entrada',
        ]);
        $this->carrilSalidaId = DB::table('carriles')->insertGetId([
            'sede_id' => $this->sedeId,
            'nombre' => 'Salida 1',
            'tipo' => 'salida',
        ]);
        $this->operador = User::factory()->create(['email' => 'operador@test.local', 'rol' => 'operador']);
        $this->administrador = User::factory()->create(['email' => 'admin@test.local', 'rol' => 'administrador']);
    }

    public function test_ocupacion_usa_el_ultimo_evento_por_placa_y_sede_y_limita_disponibles(): void
    {
        $this->evento('ABC123', 'ingreso', '2026-11-20 07:00:00');
        $this->evento('ABC123', 'salida', '2026-11-20 08:00:00', $this->carrilSalidaId);
        $this->evento('ABC123', 'ingreso', '2026-11-20 09:00:00');
        $this->evento('DEF456', 'ingreso', '2026-11-20 07:10:00');
        $this->evento('DEF456', 'ingreso', '2026-11-20 07:11:00');
        $this->evento('GHI789', 'ingreso', '2026-11-20 07:20:00');
        $this->evento('GHI789', 'salida', '2026-11-20 09:10:00', $this->carrilSalidaId);

        $this->actingAs($this->operador)
            ->getJson($this->urlResumen('2026-11-20', '2026-11-20'))
            ->assertOk()
            ->assertJsonPath('ocupacion.0.dentro', 2)
            ->assertJsonPath('ocupacion.0.total', 1)
            ->assertJsonPath('ocupacion.0.disponibles', 0)
            ->assertJsonPath('ocupacion.0.sobrecupo', true)
            ->assertJsonPath('ocupacion.0.porcentaje', 200);
    }

    public function test_recaudo_usa_pagos_en_limites_del_dia_operativo_y_separa_cobros_pendientes(): void
    {
        $cobroPendiente = $this->cobro('AAA111', 'pendiente', 12000, '2026-11-20');
        $cobroPagado = $this->cobro('BBB222', 'pagado', 9000, '2026-11-20');
        $this->pago($cobroPagado, 'A-0530', '2026-11-20 05:30:00', 10);
        $this->pago($cobroPagado, 'A-0630', '2026-11-20 06:30:00', 20);
        $this->pago($cobroPagado, 'A-0530-NEXT', '2026-11-21 05:30:00', 30);
        $this->pago($cobroPagado, 'A-0630-NEXT', '2026-11-21 06:30:00', 40);
        $this->assertDatabaseHas('cobros', ['id' => $cobroPendiente, 'estado' => 'pendiente']);

        $this->actingAs($this->administrador)
            ->getJson($this->urlResumen('2026-11-20', '2026-11-20'))
            ->assertOk()
            ->assertJsonPath('recaudo.total', 50)
            ->assertJsonPath('cobros.pendientes.cantidad', 1)
            ->assertJsonPath('cobros.pendientes.valor', 12000);
    }

    public function test_el_modo_temporal_abierto_omite_recaudo_para_el_usuario_operador_de_sesion(): void
    {
        $this->actingAs($this->operador)
            ->getJson($this->urlResumen('2026-11-20', '2026-11-20'))
            ->assertOk()
            ->assertJsonMissingPath('recaudo');

        $this->actingAs($this->operador)
            ->getJson('/api/dashboard/comparativo-sedes?desde=2026-11-20&hasta=2026-11-20')
            ->assertOk();
    }

    public function test_administrador_recibe_recaudo_y_comparativo(): void
    {
        $this->actingAs($this->administrador)
            ->getJson($this->urlResumen('2026-11-20', '2026-11-20'))
            ->assertOk()
            ->assertJsonPath('recaudo.total', 0);

        $this->actingAs($this->administrador)
            ->getJson('/api/dashboard/comparativo-sedes?desde=2026-11-20&hasta=2026-11-20')
            ->assertOk()
            ->assertJsonPath('0.sede.nombre', 'Sede Centro');
    }

    public function test_lecturas_manuales_y_baja_confianza_usan_sus_filtros(): void
    {
        DB::table('configuracion')->insert(['clave' => 'umbral_confianza', 'valor' => '80']);
        $this->evento('MAN123', 'ingreso', '2026-11-20 08:00:00', $this->carrilEntradaId, null, true);
        $this->evento('LOW123', 'ingreso', '2026-11-20 09:00:00', $this->carrilEntradaId, 79.9);
        $this->evento('OK1234', 'ingreso', '2026-11-20 10:00:00', $this->carrilEntradaId, 80);

        $this->actingAs($this->operador)
            ->getJson($this->urlResumen('2026-11-20', '2026-11-20'))
            ->assertOk()
            ->assertJsonPath('lecturas_manuales.cantidad', 1)
            ->assertJsonPath('lecturas_manuales.recientes_baja_confianza.0.placa', 'LOW123')
            ->assertJsonCount(1, 'lecturas_manuales.recientes_baja_confianza');
    }

    public function test_catalogo_de_sedes_y_actividad_reciente_tienen_formato_directo(): void
    {
        $this->evento('ABC123', 'ingreso', '2026-11-20 08:00:00');

        $this->actingAs($this->operador)
            ->getJson('/api/dashboard/sedes')
            ->assertOk()
            ->assertJsonPath('sedes.0.id', $this->sedeId)
            ->assertJsonStructure(['sedes' => [['id', 'nombre']], 'hoy_operativo']);

        $this->actingAs($this->operador)
            ->getJson('/api/dashboard/actividad?sede_id='.$this->sedeId.'&limite=1')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.tipo_vehiculo', 'carro')
            ->assertJsonPath('0.lectura_manual', false);
    }

    public function test_no_exige_autenticacion_y_sigue_validando_sede_y_rango(): void
    {
        $this->getJson('/api/dashboard/sedes')->assertOk();

        $this->getJson('/api/dashboard/resumen?sede_id=9999&desde=2026-11-22&hasta=2026-11-20')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sede_id', 'hasta']);

        $this->getJson('/api/dashboard/actividad?sede_id='.$this->sedeId.'&limite=51')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limite']);
    }

    private function evento(
        string $placa,
        string $tipo,
        string $fecha,
        ?int $carrilId = null,
        ?float $confianza = 95,
        bool $lecturaManual = false,
    ): int {
        return DB::table('eventos_acceso')->insertGetId([
            'carril_id' => $carrilId ?? $this->carrilEntradaId,
            'tipo_vehiculo_id' => $this->tipoId,
            'placa' => $placa,
            'tipo' => $tipo,
            'confianza' => $confianza,
            'lectura_manual' => $lecturaManual,
            'fecha_hora' => $fecha,
        ]);
    }

    private function cobro(string $placa, string $estado, int $valor, string $diaOperativo): int
    {
        $eventoId = $this->evento($placa, 'ingreso', $diaOperativo.' 08:00:00');

        return DB::table('cobros')->insertGetId([
            'sede_id' => $this->sedeId,
            'tarifa_id' => $this->tarifaId,
            'evento_ingreso_id' => $eventoId,
            'placa' => $placa,
            'dia_operativo' => $diaOperativo,
            'valor' => $valor,
            'estado' => $estado,
        ]);
    }

    private function pago(int $cobroId, string $referencia, string $fecha, int $valor): void
    {
        DB::table('pagos')->insert([
            'cobro_id' => $cobroId,
            'usuario_id' => $this->operador->id,
            'medio' => 'efectivo',
            'referencia' => $referencia,
            'valor' => $valor,
            'fecha_hora' => $fecha,
            'pase_hasta' => '2026-11-21 23:00:00',
        ]);
    }

    private function urlResumen(string $desde, string $hasta): string
    {
        return '/api/dashboard/resumen?sede_id='.$this->sedeId.'&desde='.$desde.'&hasta='.$hasta;
    }
}
