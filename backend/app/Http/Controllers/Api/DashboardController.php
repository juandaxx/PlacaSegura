<?php

namespace App\Http\Controllers\Api;

use App\Application\Services\PeriodoDashboard;
use App\Application\UseCases\ListarSedesDashboard;
use App\Application\UseCases\ObtenerActividadDashboard;
use App\Application\UseCases\ObtenerComparativoDashboard;
use App\Application\UseCases\ObtenerResumenDashboard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ActividadDashboardRequest;
use App\Http\Requests\Dashboard\ComparativoDashboardRequest;
use App\Http\Requests\Dashboard\ResumenDashboardRequest;
use App\Http\Resources\Dashboard\ActividadDashboardResource;
use App\Http\Resources\Dashboard\ComparativoDashboardResource;
use App\Http\Resources\Dashboard\ResumenDashboardResource;
use App\Http\Resources\Dashboard\SedesDashboardResource;

class DashboardController extends Controller
{
    public function sedes(ListarSedesDashboard $listar, PeriodoDashboard $periodos): SedesDashboardResource
    {
        return new SedesDashboardResource([
            'sedes' => $listar->ejecutar(),
            'hoy_operativo' => $periodos->hoy(),
        ]);
    }

    public function resumen(ResumenDashboardRequest $request, ObtenerResumenDashboard $obtener, PeriodoDashboard $periodos): ResumenDashboardResource
    {
        $filtros = $request->validated();
        $resumen = $obtener->ejecutar(
            sedeId: (int) $filtros['sede_id'],
            desde: $filtros['desde'],
            hasta: $filtros['hasta'],
            rol: (string) ($request->user()?->rol ?? 'administrador'),
        );
        $resumen['periodo'] = ['desde' => $filtros['desde'], 'hasta' => $filtros['hasta']];
        $resumen['umbral_alerta_capacidad'] = (float) config('placasegura.umbral_alerta_capacidad', 90);

        return new ResumenDashboardResource($resumen);
    }

    public function actividad(ActividadDashboardRequest $request, ObtenerActividadDashboard $obtener): ActividadDashboardResource
    {
        $filtros = $request->validated();

        return new ActividadDashboardResource($obtener->ejecutar((int) $filtros['sede_id'], (int) $filtros['limite']));
    }

    public function comparativo(ComparativoDashboardRequest $request, ObtenerComparativoDashboard $obtener): ComparativoDashboardResource
    {
        $filtros = $request->validated();

        return new ComparativoDashboardResource($obtener->ejecutar($filtros['desde'], $filtros['hasta']));
    }
}
