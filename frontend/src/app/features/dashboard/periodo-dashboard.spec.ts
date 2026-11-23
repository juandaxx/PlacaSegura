import { resolverPeriodoDashboard } from './periodo-dashboard';

describe('resolverPeriodoDashboard', () => {
  it('usa el día operativo recibido por el backend para hoy y ayer', () => {
    expect(resolverPeriodoDashboard('hoy', '2026-11-22')).toEqual({ desde: '2026-11-22', hasta: '2026-11-22' });
    expect(resolverPeriodoDashboard('ayer', '2026-11-22')).toEqual({ desde: '2026-11-21', hasta: '2026-11-21' });
  });

  it('incluye siete días operativos contando hoy', () => {
    expect(resolverPeriodoDashboard('7dias', '2026-11-22')).toEqual({ desde: '2026-11-16', hasta: '2026-11-22' });
  });

  it('conserva el rango seleccionado por el usuario', () => {
    const rango = { desde: '2026-11-01', hasta: '2026-11-05' };
    expect(resolverPeriodoDashboard('rango', '2026-11-22', rango)).toEqual(rango);
  });
});
