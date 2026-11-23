export interface SedeDashboard {
  readonly id: number;
  readonly nombre: string;
}

export interface CatalogoDashboard {
  readonly sedes: readonly SedeDashboard[];
  readonly hoy_operativo: string;
}

export interface FiltroPeriodoDashboard {
  readonly desde: string;
  readonly hasta: string;
}

export interface OcupacionDashboard {
  readonly tipo: string;
  readonly total: number;
  readonly dentro: number;
  readonly disponibles: number;
  readonly porcentaje: number;
  readonly sobrecupo: boolean;
}

export interface TotalesCobroDashboard {
  readonly cantidad: number;
  readonly valor: number;
}

export interface ResumenDashboard {
  readonly sede: SedeDashboard;
  readonly periodo: FiltroPeriodoDashboard;
  readonly umbral_alerta_capacidad: number;
  readonly ocupacion: readonly OcupacionDashboard[];
  readonly movimientos: { readonly ingresos: number; readonly salidas: number };
  readonly cobros: {
    readonly pendientes: TotalesCobroDashboard;
    readonly vencidos: TotalesCobroDashboard;
  };
  readonly recaudo?: { readonly total: number };
  readonly lecturas_manuales: {
    readonly cantidad: number;
    readonly recientes_baja_confianza: readonly LecturaBajaConfianzaDashboard[];
  };
}

export interface LecturaBajaConfianzaDashboard {
  readonly placa: string;
  readonly confianza: number;
  readonly fecha_hora: string;
}

export interface EventoDashboard {
  readonly placa: string;
  readonly tipo_vehiculo: string;
  readonly tipo: 'ingreso' | 'salida';
  readonly carril: string;
  readonly fecha_hora: string;
  readonly lectura_manual: boolean;
  readonly confianza: number | null;
}

export interface SedeComparativoDashboard {
  readonly sede: SedeDashboard;
  readonly dentro: number;
  readonly total: number;
  readonly porcentaje_ocupacion: number;
  readonly recaudo: number;
  readonly pendientes: TotalesCobroDashboard;
  readonly alerta_capacidad: boolean;
}
