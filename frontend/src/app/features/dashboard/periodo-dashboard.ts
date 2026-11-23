import { FiltroPeriodoDashboard } from '../../core/models/dashboard.models';

export type OpcionPeriodoDashboard = 'hoy' | 'ayer' | '7dias' | 'rango';

export function resolverPeriodoDashboard(
  opcion: OpcionPeriodoDashboard,
  hoyOperativo: string,
  rangoPersonalizado?: FiltroPeriodoDashboard,
): FiltroPeriodoDashboard {
  if (opcion === 'rango') {
    return rangoPersonalizado ?? { desde: hoyOperativo, hasta: hoyOperativo };
  }

  if (opcion === 'hoy') {
    return { desde: hoyOperativo, hasta: hoyOperativo };
  }

  const fechaHasta = opcion === 'ayer' ? desplazarFecha(hoyOperativo, -1) : hoyOperativo;
  const fechaDesde = opcion === '7dias' ? desplazarFecha(hoyOperativo, -6) : fechaHasta;
  return { desde: fechaDesde, hasta: fechaHasta };
}

function desplazarFecha(fecha: string, dias: number): string {
  const valor = new Date(`${fecha}T00:00:00.000Z`);
  valor.setUTCDate(valor.getUTCDate() + dias);
  return valor.toISOString().slice(0, 10);
}
