<?php

namespace Tests\Feature\Api;

use App\Domain\Contracts\Reloj;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Dobles\RelojFijo;
use Tests\TestCase;

class AccesoEndpointTest extends TestCase
{
    use RefreshDatabase;

    private RelojFijo $reloj;

    private int $sedeId;

    private int $entradaId;

    private int $salidaId;

    private int $carroId;

    private int $operadorId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reloj = new RelojFijo('2026-11-20 08:00:00');
        $this->app->instance(Reloj::class, $this->reloj);

        $this->sedeId = DB::table('sedes')->insertGetId(['nombre' => 'Sede Centro', 'direccion' => 'Calle 1 # 2-3']);
        $this->entradaId = DB::table('carriles')->insertGetId(['sede_id' => $this->sedeId, 'nombre' => 'Entrada 1', 'tipo' => 'entrada']);
        $this->salidaId = DB::table('carriles')->insertGetId(['sede_id' => $this->sedeId, 'nombre' => 'Salida 1', 'tipo' => 'salida']);

        $this->carroId = DB::table('tipos_vehiculo')->insertGetId(['nombre' => 'carro', 'patron_placa' => '^[A-Z]{3}[0-9]{3}$']);
        $motoId = DB::table('tipos_vehiculo')->insertGetId(['nombre' => 'moto', 'patron_placa' => '^[A-Z]{3}[0-9]{2}[A-Z]$']);

        $diurnaId = DB::table('franjas')->insertGetId(['nombre' => 'diurna', 'hora_inicio' => '06:00:00', 'hora_fin' => '18:00:00']);
        $nocturnaId = DB::table('franjas')->insertGetId(['nombre' => 'nocturna', 'hora_inicio' => '18:00:00', 'hora_fin' => '06:00:00']);

        foreach ([
            [$this->carroId, $diurnaId, 5000],
            [$this->carroId, $nocturnaId, 3000],
            [$motoId, $diurnaId, 0],
            [$motoId, $nocturnaId, 0],
        ] as [$tipoVehiculoId, $franjaId, $valor]) {
            DB::table('tarifas')->insert([
                'sede_id' => $this->sedeId, 'tipo_vehiculo_id' => $tipoVehiculoId, 'franja_id' => $franjaId, 'valor' => $valor,
            ]);
        }

        DB::table('cupos')->insert([
            ['sede_id' => $this->sedeId, 'tipo_vehiculo_id' => $this->carroId, 'total' => 5],
            ['sede_id' => $this->sedeId, 'tipo_vehiculo_id' => $motoId, 'total' => 5],
        ]);
        DB::table('configuracion')->insert(['clave' => 'umbral_confianza', 'valor' => '0.80']);

        $this->operadorId = DB::table('usuarios')->insertGetId([
            'nombre' => 'Operador Carril', 'email' => 'operador@placasegura.test',
            'password' => bcrypt('secret'), 'rol' => 'operador', 'activo' => true,
        ]);
    }

    // Escenarios minimos de la demo (seccion 7.2 del enunciado)

    public function test_escenario_1_una_moto_entra_y_sale_sin_cobro(): void
    {
        $this->ingreso('XYZ12A')
            ->assertOk()
            ->assertJsonPath('barrera', 'abierta')
            ->assertJsonPath('motivo', 'OK')
            ->assertJsonPath('tipo_vehiculo', 'moto')
            ->assertJsonPath('cobro.valor', 0);

        $this->salida('XYZ12A')
            ->assertOk()
            ->assertJsonPath('barrera', 'abierta')
            ->assertJsonPath('motivo', 'OK');
    }

    public function test_escenario_2_un_carro_entra_en_franja_diurna_y_queda_con_cobro_pendiente(): void
    {
        $this->ingreso('ABC123')
            ->assertOk()
            ->assertJsonPath('barrera', 'abierta')
            ->assertJsonPath('motivo', 'OK')
            ->assertJsonPath('placa', 'ABC123')
            ->assertJsonPath('tipo_vehiculo', 'carro')
            ->assertJsonPath('lectura_manual', false)
            ->assertJsonPath('cobro.valor', 5000)
            ->assertJsonPath('cobro.estado', 'pendiente');

        $this->assertDatabaseHas('eventos_acceso', [
            'placa' => 'ABC123', 'tipo' => 'ingreso', 'motivo' => 'OK', 'carril_id' => $this->entradaId,
        ]);
        $this->assertDatabaseHas('cobros', [
            'placa' => 'ABC123', 'dia_operativo' => '2026-11-20', 'valor' => 5000, 'estado' => 'pendiente',
        ]);
    }

    public function test_escenario_3_el_carro_intenta_salir_sin_pagar_y_la_barrera_no_abre(): void
    {
        $cobroId = $this->ingreso('ABC123')->json('cobro.id');

        $this->salida('ABC123')
            ->assertOk()
            ->assertJsonPath('barrera', 'cerrada')
            ->assertJsonPath('motivo', 'COBRO_PENDIENTE')
            ->assertJsonPath('cobro.id', $cobroId)
            ->assertJsonPath('cobro.estado', 'pendiente');
    }

    public function test_escenario_4_paga_sale_y_vuelve_a_entrar_sin_cobro_nuevo(): void
    {
        $cobroId = $this->ingreso('ABC123')->json('cobro.id');
        $this->pagar($cobroId)->assertCreated();

        $this->reloj->fijar('2026-11-20 12:00:00');
        $this->salida('ABC123')
            ->assertJsonPath('barrera', 'abierta')
            ->assertJsonPath('motivo', 'PASE_VIGENTE');

        $this->reloj->fijar('2026-11-20 15:00:00');
        $this->ingreso('ABC123')
            ->assertJsonPath('barrera', 'abierta')
            ->assertJsonPath('motivo', 'PASE_VIGENTE')
            ->assertJsonPath('cobro.id', $cobroId)
            ->assertJsonPath('cobro.estado', 'pagado');

        $this->assertDatabaseCount('cobros', 1);
    }

    public function test_escenario_5_una_lectura_con_baja_confianza_pasa_a_validacion_manual(): void
    {
        $eventoId = $this->ingreso('ABC128', confianza: 0.42)
            ->assertOk()
            ->assertJsonPath('barrera', 'cerrada')
            ->assertJsonPath('motivo', 'LECTURA_MANUAL')
            ->assertJsonPath('cobro', null)
            ->json('evento_id');

        $this->assertDatabaseCount('cobros', 0);

        // El operador corrige la placa y se repite el flujo de ingreso.
        $this->validarPlaca($eventoId, 'ABC123')
            ->assertOk()
            ->assertJsonPath('evento_id', $eventoId)
            ->assertJsonPath('barrera', 'abierta')
            ->assertJsonPath('motivo', 'OK')
            ->assertJsonPath('placa', 'ABC123')
            ->assertJsonPath('lectura_manual', true)
            ->assertJsonPath('cobro.valor', 5000);

        $this->assertDatabaseHas('eventos_acceso', [
            'id' => $eventoId,
            'placa' => 'ABC123',
            'lectura_manual' => true,
            'usuario_id' => $this->operadorId,
            'motivo' => 'OK',
        ]);
    }

    public function test_escenario_6_sin_cupo_para_el_tipo_de_vehiculo_muestra_lleno(): void
    {
        DB::table('cupos')->where('tipo_vehiculo_id', $this->carroId)->update(['total' => 1]);
        $this->ingreso('ABC123')->assertJsonPath('motivo', 'OK');

        $this->ingreso('DEF456')
            ->assertOk()
            ->assertJsonPath('barrera', 'cerrada')
            ->assertJsonPath('motivo', 'SIN_CUPO')
            ->assertJsonPath('cobro', null);

        // Las motos tienen su propio cupo.
        $this->ingreso('XYZ12A')->assertJsonPath('motivo', 'OK');
    }

    public function test_cuando_un_vehiculo_sale_libera_su_cupo(): void
    {
        DB::table('cupos')->where('tipo_vehiculo_id', $this->carroId)->update(['total' => 1]);
        $this->pagar($this->ingreso('ABC123')->json('cobro.id'))->assertCreated();
        $this->salida('ABC123')->assertJsonPath('barrera', 'abierta');

        $this->ingreso('DEF456')->assertJsonPath('motivo', 'OK');
    }

    // RN-04: un solo cobro por placa, sede y dia operativo

    public function test_rn04_un_segundo_ingreso_el_mismo_dia_operativo_no_genera_otro_cobro(): void
    {
        $cobroId = $this->ingreso('ABC123')->json('cobro.id');

        $this->reloj->fijar('2026-11-21 05:30:00'); // Sigue siendo el dia operativo del 20.
        $this->ingreso('ABC123')->assertOk()->assertJsonPath('cobro.id', $cobroId);

        $this->assertDatabaseCount('cobros', 1);
    }

    public function test_rn04_la_base_de_datos_rechaza_un_segundo_cobro_del_mismo_dia(): void
    {
        $cobro = (array) DB::table('cobros')->find($this->ingreso('ABC123')->json('cobro.id'));
        unset($cobro['id']);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('cobros')->insert($cobro);
    }

    // Franjas y dia operativo

    public function test_de_noche_aplica_la_tarifa_nocturna_y_el_dia_operativo_anterior(): void
    {
        $this->reloj->fijar('2026-11-21 02:00:00');

        $this->ingreso('ABC123')->assertOk()->assertJsonPath('cobro.valor', 3000);

        $this->assertDatabaseHas('cobros', ['placa' => 'ABC123', 'dia_operativo' => '2026-11-20', 'valor' => 3000]);
    }

    public function test_un_carro_que_entro_de_noche_no_sale_sin_pagar_al_dia_siguiente(): void
    {
        $this->reloj->fijar('2026-11-20 23:00:00');
        $this->ingreso('ABC123');

        $this->reloj->fijar('2026-11-21 07:00:00');
        $this->salida('ABC123')
            ->assertJsonPath('barrera', 'cerrada')
            ->assertJsonPath('motivo', 'COBRO_PENDIENTE');
    }

    // Validacion manual de la placa

    public function test_una_salida_con_baja_confianza_tambien_pasa_a_validacion_manual(): void
    {
        $this->ingreso('ABC123');
        $eventoId = $this->salida('ABC123', confianza: 0.3)
            ->assertJsonPath('motivo', 'LECTURA_MANUAL')
            ->json('evento_id');

        $this->validarPlaca($eventoId, 'ABC123')
            ->assertOk()
            ->assertJsonPath('barrera', 'cerrada')
            ->assertJsonPath('motivo', 'COBRO_PENDIENTE');
    }

    public function test_solo_se_valida_un_evento_pendiente_de_validacion_manual(): void
    {
        $eventoId = $this->ingreso('ABC123')->json('evento_id');

        $this->validarPlaca($eventoId, 'ABC123')->assertConflict();
        $this->validarPlaca(9999, 'ABC123')->assertNotFound();
    }

    // Validacion de la peticion

    public function test_la_placa_se_normaliza_y_debe_tener_un_formato_reconocido(): void
    {
        $this->ingreso('abc 123')->assertOk()->assertJsonPath('placa', 'ABC123');

        $this->ingreso('AB1234')->assertUnprocessable()->assertJsonValidationErrors(['placa']);

        $this->assertDatabaseCount('eventos_acceso', 1);
    }

    public function test_valida_la_lectura_y_el_carril(): void
    {
        $this->postJson('/api/accesos/ingreso', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['placa', 'confianza', 'sede_id', 'carril_id']);

        $this->ingreso('ABC123', confianza: 1.5)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['confianza']);

        // Un carril de salida no sirve para ingresar.
        $this->postJson('/api/accesos/ingreso', $this->lectura('ABC123', 0.9, $this->salidaId))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['carril_id']);
    }

    public function test_sin_tarifa_configurada_responde_409_y_no_registra_nada(): void
    {
        DB::table('tarifas')->delete();

        $this->ingreso('ABC123')->assertConflict()->assertJsonStructure(['message']);

        $this->assertDatabaseCount('eventos_acceso', 0);
        $this->assertDatabaseCount('cobros', 0);
    }

    private function ingreso(string $placa, float $confianza = 0.95): TestResponse
    {
        return $this->postJson('/api/accesos/ingreso', $this->lectura($placa, $confianza, $this->entradaId));
    }

    private function salida(string $placa, float $confianza = 0.95): TestResponse
    {
        return $this->postJson('/api/accesos/salida', $this->lectura($placa, $confianza, $this->salidaId));
    }

    /**
     * @return array<string, mixed>
     */
    private function lectura(string $placa, float $confianza, int $carrilId): array
    {
        return ['placa' => $placa, 'confianza' => $confianza, 'sede_id' => $this->sedeId, 'carril_id' => $carrilId];
    }

    private function validarPlaca(int $eventoId, string $placa): TestResponse
    {
        // Mientras no exista el login, el operador se envia en la peticion.
        return $this->patchJson("/api/eventos/{$eventoId}/placa", ['placa' => $placa, 'usuario_id' => $this->operadorId]);
    }

    private function pagar(int $cobroId): TestResponse
    {
        return $this->postJson("/api/cobros/{$cobroId}/pagos", [
            'medio' => 'efectivo',
            'referencia' => "CAJA1-{$cobroId}",
            'usuario_id' => $this->operadorId,
        ]);
    }
}
