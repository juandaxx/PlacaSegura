<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\ConsultasDashboard;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Consultas agregadas del dashboard, optimizadas para PostgreSQL. */
final class DbConsultasDashboard implements ConsultasDashboard
{
    public function resumen(int $sedeId, DateTimeImmutable $inicio, DateTimeImmutable $finExclusivo, bool $esAdministrador): array
    {
        $sede = DB::table('sedes')->where('id', $sedeId)->first();
        if ($sede === null) {
            throw new RuntimeException('La sede solicitada ya no existe.');
        }

        $ocupacion = $this->ocupacionPorTipo($sedeId);
        $eventos = DB::table('eventos_acceso as e')
            ->join('carriles as ca', 'ca.id', '=', 'e.carril_id')
            ->where('ca.sede_id', $sedeId)
            ->where('e.fecha_hora', '>=', $this->fechaSql($inicio))
            ->where('e.fecha_hora', '<', $this->fechaSql($finExclusivo));

        $movimientos = (clone $eventos)
            ->selectRaw("SUM(CASE WHEN e.tipo = 'ingreso' THEN 1 ELSE 0 END) AS ingresos")
            ->selectRaw("SUM(CASE WHEN e.tipo = 'salida' THEN 1 ELSE 0 END) AS salidas")
            ->first();

        $desdeOperativo = $inicio->format('Y-m-d');
        $hastaOperativo = $finExclusivo->modify('-1 day')->format('Y-m-d');
        $totalesCobros = DB::table('cobros')
            ->where('sede_id', $sedeId)
            ->whereBetween('dia_operativo', [$desdeOperativo, $hastaOperativo])
            ->whereIn('estado', ['pendiente', 'vencido'])
            ->select('estado')
            ->selectRaw('COUNT(*) AS cantidad, COALESCE(SUM(valor), 0) AS valor')
            ->groupBy('estado')
            ->get()
            ->keyBy('estado');

        $lecturasManual = (clone $eventos)->where('e.lectura_manual', true)->count();
        $confianzaConfigurada = DB::table('configuracion')
            ->where('clave', 'umbral_confianza')
            ->value('valor');
        $umbralConfianza = is_numeric($confianzaConfigurada)
            ? (float) $confianzaConfigurada
            : (float) config('placasegura.umbral_confianza', 70);

        $lecturasBajaConfianza = (clone $eventos)
            ->whereNotNull('e.confianza')
            ->where('e.confianza', '<', $umbralConfianza)
            ->orderByDesc('e.fecha_hora')
            ->orderByDesc('e.id')
            ->limit(5)
            ->get(['e.placa', 'e.confianza', 'e.fecha_hora'])
            ->map(fn (object $fila): array => [
                'placa' => $fila->placa,
                'confianza' => (float) $fila->confianza,
                'fecha_hora' => $fila->fecha_hora,
            ])
            ->all();

        $respuesta = [
            'sede' => ['id' => (int) $sede->id, 'nombre' => $sede->nombre],
            'ocupacion' => $ocupacion,
            'movimientos' => [
                'ingresos' => (int) ($movimientos->ingresos ?? 0),
                'salidas' => (int) ($movimientos->salidas ?? 0),
            ],
            'cobros' => [
                'pendientes' => $this->grupoCobro($totalesCobros->get('pendiente')),
                'vencidos' => $this->grupoCobro($totalesCobros->get('vencido')),
            ],
            'lecturas_manuales' => [
                'cantidad' => $lecturasManual,
                'recientes_baja_confianza' => $lecturasBajaConfianza,
            ],
        ];

        if ($esAdministrador) {
            $respuesta['recaudo'] = ['total' => $this->recaudoSede($sedeId, $inicio, $finExclusivo)];
        }

        return $respuesta;
    }

    public function actividad(int $sedeId, int $limite): array
    {
        return DB::table('eventos_acceso as e')
            ->join('carriles as ca', 'ca.id', '=', 'e.carril_id')
            ->join('tipos_vehiculo as tv', 'tv.id', '=', 'e.tipo_vehiculo_id')
            ->where('ca.sede_id', $sedeId)
            ->orderByDesc('e.fecha_hora')
            ->orderByDesc('e.id')
            ->limit($limite)
            ->get([
                'e.placa',
                'tv.nombre as tipo_vehiculo',
                'e.tipo',
                'ca.nombre as carril',
                'e.fecha_hora',
                'e.lectura_manual',
                'e.confianza',
            ])
            ->map(fn (object $fila): array => [
                'placa' => $fila->placa,
                'tipo_vehiculo' => $fila->tipo_vehiculo,
                'tipo' => $fila->tipo,
                'carril' => $fila->carril,
                'fecha_hora' => $fila->fecha_hora,
                'lectura_manual' => (bool) $fila->lectura_manual,
                'confianza' => $fila->confianza === null ? null : (float) $fila->confianza,
            ])
            ->all();
    }

    public function comparativo(DateTimeImmutable $inicio, DateTimeImmutable $finExclusivo): array
    {
        $ultimos = $this->ultimosEventos();
        $ingresos = DB::query()->fromSub($ultimos, 'u')
            ->where('u.tipo', 'ingreso')
            ->select('u.sede_id', 'u.tipo_vehiculo_id')
            ->selectRaw('COUNT(*) AS dentro')
            ->groupBy('u.sede_id', 'u.tipo_vehiculo_id');

        $dentroPorSede = DB::query()->fromSub(clone $ingresos, 'i')
            ->select('i.sede_id')
            ->selectRaw('SUM(i.dentro) AS dentro')
            ->groupBy('i.sede_id');

        $cuposPorSede = DB::table('cupos')
            ->select('sede_id')
            ->selectRaw('SUM(total) AS total')
            ->groupBy('sede_id');

        $umbral = (float) config('placasegura.umbral_alerta_capacidad', 90);
        $sedesEnAlerta = DB::table('cupos as c')
            ->leftJoinSub(clone $ingresos, 'i', function ($join): void {
                $join->on('i.sede_id', '=', 'c.sede_id')
                    ->on('i.tipo_vehiculo_id', '=', 'c.tipo_vehiculo_id');
            })
            ->where('c.total', '>', 0)
            ->whereRaw('COALESCE(i.dentro, 0) * 100.0 / c.total >= ?', [$umbral])
            ->select('c.sede_id')
            ->distinct();

        $recaudoPorSede = DB::table('pagos as p')
            ->join('cobros as co', 'co.id', '=', 'p.cobro_id')
            ->where('p.fecha_hora', '>=', $this->fechaSql($inicio))
            ->where('p.fecha_hora', '<', $this->fechaSql($finExclusivo))
            ->select('co.sede_id')
            ->selectRaw('COALESCE(SUM(p.valor), 0) AS recaudo')
            ->groupBy('co.sede_id');

        $pendientesPorSede = DB::table('cobros')
            ->where('estado', 'pendiente')
            ->select('sede_id')
            ->selectRaw('COUNT(*) AS cantidad_pendiente, COALESCE(SUM(valor), 0) AS valor_pendiente')
            ->groupBy('sede_id');

        return DB::table('sedes as s')
            ->leftJoinSub($dentroPorSede, 'd', 'd.sede_id', '=', 's.id')
            ->leftJoinSub($cuposPorSede, 'c', 'c.sede_id', '=', 's.id')
            ->leftJoinSub($sedesEnAlerta, 'a', 'a.sede_id', '=', 's.id')
            ->leftJoinSub($recaudoPorSede, 'r', 'r.sede_id', '=', 's.id')
            ->leftJoinSub($pendientesPorSede, 'p', 'p.sede_id', '=', 's.id')
            ->orderBy('s.nombre')
            ->get([
                's.id',
                's.nombre',
                's.direccion',
                'd.dentro',
                'c.total',
                'a.sede_id as sede_en_alerta',
                'r.recaudo',
                'p.cantidad_pendiente',
                'p.valor_pendiente',
            ])
            ->map(function (object $fila): array {
                $total = (int) ($fila->total ?? 0);
                $dentro = (int) ($fila->dentro ?? 0);

                return [
                    'sede' => ['id' => (int) $fila->id, 'nombre' => $fila->nombre],
                    'dentro' => $dentro,
                    'total' => $total,
                    'porcentaje_ocupacion' => $total > 0 ? round($dentro * 100 / $total, 1) : 0.0,
                    'recaudo' => (float) ($fila->recaudo ?? 0),
                    'pendientes' => [
                        'cantidad' => (int) ($fila->cantidad_pendiente ?? 0),
                        'valor' => (float) ($fila->valor_pendiente ?? 0),
                    ],
                    'alerta_capacidad' => $fila->sede_en_alerta !== null,
                ];
            })
            ->all();
    }

    public function sedes(): array
    {
        return DB::table('sedes')
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn (object $fila): array => ['id' => (int) $fila->id, 'nombre' => $fila->nombre])
            ->all();
    }

    private function ocupacionPorTipo(int $sedeId): array
    {
        $ingresos = DB::query()->fromSub($this->ultimosEventos(), 'u')
            ->where('u.sede_id', $sedeId)
            ->where('u.tipo', 'ingreso')
            ->select('u.tipo_vehiculo_id')
            ->selectRaw('COUNT(*) AS dentro')
            ->groupBy('u.tipo_vehiculo_id');

        return DB::table('tipos_vehiculo as tv')
            ->leftJoin('cupos as c', function ($join) use ($sedeId): void {
                $join->on('c.tipo_vehiculo_id', '=', 'tv.id')->where('c.sede_id', '=', $sedeId);
            })
            ->leftJoinSub($ingresos, 'i', 'i.tipo_vehiculo_id', '=', 'tv.id')
            ->orderBy('tv.nombre')
            ->get([
                'tv.nombre as tipo_vehiculo',
                'c.total',
                'i.dentro',
            ])
            ->map(function (object $fila): array {
                $total = (int) ($fila->total ?? 0);
                $dentro = (int) ($fila->dentro ?? 0);

                return [
                    'tipo' => $fila->tipo_vehiculo,
                    'total' => $total,
                    'dentro' => $dentro,
                    'disponibles' => max(0, $total - $dentro),
                    'porcentaje' => $total > 0 ? round($dentro * 100 / $total, 1) : 0.0,
                    'sobrecupo' => $dentro > $total,
                ];
            })
            ->all();
    }

    /**
     * Obtiene la última lectura por placa y sede. El conteo es una estimación:
     * ingresos duplicados o datos corregidos pueden distorsionar la ocupación.
     */
    private function ultimosEventos(): Builder
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            return DB::table('eventos_acceso as e')
                ->join('carriles as ca', 'ca.id', '=', 'e.carril_id')
                ->selectRaw('DISTINCT ON (ca.sede_id, e.placa) ca.sede_id, e.placa, e.tipo_vehiculo_id, e.tipo, e.fecha_hora, e.id')
                ->orderBy('ca.sede_id')
                ->orderBy('e.placa')
                ->orderByDesc('e.fecha_hora')
                ->orderByDesc('e.id');
        }

        $clasificados = DB::table('eventos_acceso as e')
            ->join('carriles as ca', 'ca.id', '=', 'e.carril_id')
            ->select('ca.sede_id', 'e.placa', 'e.tipo_vehiculo_id', 'e.tipo', 'e.fecha_hora', 'e.id')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY ca.sede_id, e.placa ORDER BY e.fecha_hora DESC, e.id DESC) AS fila');

        return DB::query()->fromSub($clasificados, 'clasificados')
            ->where('fila', 1)
            ->select('sede_id', 'placa', 'tipo_vehiculo_id', 'tipo', 'fecha_hora', 'id');
    }

    private function recaudoSede(int $sedeId, DateTimeImmutable $inicio, DateTimeImmutable $finExclusivo): float
    {
        return (float) DB::table('pagos as p')
            ->join('cobros as co', 'co.id', '=', 'p.cobro_id')
            ->where('co.sede_id', $sedeId)
            ->where('p.fecha_hora', '>=', $this->fechaSql($inicio))
            ->where('p.fecha_hora', '<', $this->fechaSql($finExclusivo))
            ->sum('p.valor');
    }

    private function grupoCobro(?object $fila): array
    {
        return [
            'cantidad' => (int) ($fila->cantidad ?? 0),
            'valor' => (float) ($fila->valor ?? 0),
        ];
    }

    private function fechaSql(DateTimeImmutable $fecha): string
    {
        return $fecha->format('Y-m-d H:i:s');
    }
}
