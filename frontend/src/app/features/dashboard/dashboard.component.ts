import { CurrencyPipe, DatePipe } from '@angular/common';
import { Component, DestroyRef, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { Observable, Subject, catchError, forkJoin, interval, map, of, startWith, switchMap } from 'rxjs';
import { AUTH_SERVICE } from '../../core/api/parking.services';
import { DashboardService } from '../../core/api/dashboard.service';
import {
  CatalogoDashboard,
  EventoDashboard,
  FiltroPeriodoDashboard,
  LecturaBajaConfianzaDashboard,
  ResumenDashboard,
  SedeComparativoDashboard,
  SedeDashboard,
} from '../../core/models/dashboard.models';
import { OccupancyCardComponent } from '../../shared/components/occupancy-card.component';
import { StatusBannerComponent } from '../../shared/components/status-banner.component';
import { IndicatorCardComponent } from '../../shared/components/indicator-card.component';
import { OpcionPeriodoDashboard, resolverPeriodoDashboard } from './periodo-dashboard';

type ResultadoBloque<T> = { readonly datos: T | null; readonly error: string | null };

@Component({
  selector: 'app-dashboard',
  imports: [CurrencyPipe, DatePipe, FormsModule, OccupancyCardComponent, StatusBannerComponent, IndicatorCardComponent],
  template: `
    <div class="space-y-7">
      <header class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Operación</p>
          <h1 class="mt-1 font-heading text-2xl font-semibold tracking-tight text-ink sm:text-3xl">Dashboard</h1>
          <p class="mt-2 text-sm text-muted">Resumen de ocupación y actividad del parqueadero.</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
          <label class="grid gap-1.5 text-xs font-semibold text-muted">Sede
            <select class="min-w-52 rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-ink disabled:cursor-wait disabled:opacity-60" [ngModel]="sedeId()" (ngModelChange)="cambiarSede($event)" [disabled]="cargandoCatalogo() || sedes().length === 0" aria-label="Seleccionar sede">
              @if (sedes().length === 0) { <option [ngValue]="null">{{ cargandoCatalogo() ? 'Cargando sedes…' : 'Sin sedes' }}</option> }
              @for (sede of sedes(); track sede.id) { <option [ngValue]="sede.id">{{ sede.nombre }}</option> }
            </select>
          </label>
          <label class="grid gap-1.5 text-xs font-semibold text-muted">Periodo
            <select class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-ink" [ngModel]="opcionPeriodo()" (ngModelChange)="cambiarOpcionPeriodo($event)" aria-label="Seleccionar periodo">
              <option value="hoy">Hoy operativo</option><option value="ayer">Ayer</option><option value="7dias">Últimos 7 días</option><option value="rango">Rango de fechas</option>
            </select>
          </label>
          @if (opcionPeriodo() === 'rango') {
            <label class="grid gap-1.5 text-xs font-semibold text-muted">Desde
              <input class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-ink" type="date" [ngModel]="desdePersonalizado()" (ngModelChange)="cambiarDesde($event)" />
            </label>
            <label class="grid gap-1.5 text-xs font-semibold text-muted">Hasta
              <input class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-ink" type="date" [ngModel]="hastaPersonalizado()" (ngModelChange)="cambiarHasta($event)" />
            </label>
          }
          <button type="button" class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-strong focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50" [disabled]="cargandoCatalogo() || sedeId() === null" (click)="actualizar()">Actualizar</button>
        </div>
      </header>

      @if (errorCatalogo()) {
        <app-status-banner kind="danger" title="No se pudieron cargar las sedes" [description]="errorCatalogo()!" icon="!" />
        <button type="button" class="text-sm font-semibold text-primary underline" (click)="reintentarCatalogo()">Reintentar</button>
      } @else if (sedes().length === 0 && !cargandoCatalogo()) {
        <app-status-banner title="No hay sedes registradas" description="Cuando se configure una sede, aparecerán aquí sus indicadores." />
      }

      @if (!errorCatalogo() && sedes().length > 0) {
      @if (esAdministrador() && resumen()?.recaudo; as recaudo) {
        <section aria-label="Recaudo del periodo" class="rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-white p-5 shadow-sm sm:flex sm:items-center sm:justify-between sm:p-6">
          <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-primary">Recaudo recibido</p><p class="mt-2 font-heading text-3xl font-semibold text-ink">{{ recaudo.total | currency:'COP':'symbol-narrow':'1.0-0' }}</p><p class="mt-1 text-sm text-muted">Pagos confirmados · separado de cobros pendientes</p></div>
          <span class="mt-4 inline-flex items-center rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-primary ring-1 ring-blue-200 sm:mt-0">{{ periodo().desde }} – {{ periodo().hasta }}</span>
        </section>
      }

      <section aria-labelledby="ocupacion-heading">
        <div class="mb-3 flex items-end justify-between gap-3"><div><h2 id="ocupacion-heading" class="font-heading text-lg font-semibold text-ink">Ocupación estimada</h2><p class="mt-1 text-xs text-muted">Se calcula con el último evento registrado por placa.</p></div><span class="text-xs text-muted">{{ periodo().desde }} – {{ periodo().hasta }}</span></div>
        @if (cargandoResumen()) { <p class="rounded-xl border border-line bg-surface p-5 text-sm text-muted" role="status">Cargando ocupación…</p> }
        @else if (errorResumen()) { <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-danger" role="alert">{{ errorResumen() }} <button class="ml-2 font-semibold underline" (click)="actualizar()">Reintentar</button></div> }
        @else if ((resumen()?.ocupacion?.length ?? 0) === 0) { <p class="rounded-xl border border-dashed border-line p-5 text-sm text-muted">No hay tipos de vehículo configurados.</p> }
        @else { <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">@for (item of resumen()!.ocupacion; track item.tipo) { <app-occupancy-card [title]="item.tipo" [occupied]="item.dentro" [total]="item.total" [alertThreshold]="resumen()!.umbral_alerta_capacidad" /> }</div> }
      </section>

      <section aria-labelledby="indicadores-heading">
        <h2 id="indicadores-heading" class="sr-only">Indicadores del periodo</h2>
        @if (cargandoResumen()) { <p class="rounded-xl border border-line bg-surface p-5 text-sm text-muted" role="status">Cargando indicadores…</p> }
        @else if (resumen(); as datos) {
          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <app-indicator-card label="Ingresos" [value]="datos.movimientos.ingresos.toString()" detail="En el periodo" icon="↘" />
            <app-indicator-card label="Salidas" [value]="datos.movimientos.salidas.toString()" detail="En el periodo" icon="↗" />
            <app-indicator-card label="Cobros pendientes" [value]="formatearDinero(datos.cobros.pendientes.valor)" [detail]="datos.cobros.pendientes.cantidad + ' cobros'" icon="$" />
            <app-indicator-card label="Cobros vencidos" [value]="formatearDinero(datos.cobros.vencidos.valor)" [detail]="datos.cobros.vencidos.cantidad + ' cobros'" icon="!" />
            <app-indicator-card label="Lecturas manuales" [value]="datos.lecturas_manuales.cantidad.toString()" detail="En el periodo" icon="✎" />
          </div>
        }
      </section>

      <section aria-labelledby="actividad-heading" class="rounded-2xl border border-line bg-surface shadow-card">
        <div class="flex items-center justify-between border-b border-line px-5 py-4"><div><h2 id="actividad-heading" class="font-heading text-lg font-semibold text-ink">Actividad reciente</h2><p class="mt-1 text-xs text-muted">Últimos eventos registrados en esta sede.</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-muted">{{ actividad().length }} eventos</span></div>
        @if (cargandoActividad()) { <p class="p-5 text-sm text-muted" role="status">Cargando actividad…</p> }
        @else if (errorActividad()) { <div class="p-5 text-sm text-danger" role="alert">{{ errorActividad() }} <button class="ml-2 font-semibold underline" (click)="actualizar()">Reintentar</button></div> }
        @else if (actividad().length === 0) { <p class="p-5 text-sm text-muted">Todavía no hay eventos de acceso.</p> }
        @else {
          <div class="overflow-x-auto"><table class="min-w-full divide-y divide-line text-left text-sm"><thead class="bg-canvas text-[11px] uppercase tracking-wide text-muted"><tr><th class="px-5 py-3 font-semibold">Placa</th><th class="px-5 py-3 font-semibold">Vehículo</th><th class="px-5 py-3 font-semibold">Movimiento</th><th class="px-5 py-3 font-semibold">Carril</th><th class="px-5 py-3 font-semibold">Hora</th><th class="px-5 py-3 font-semibold">Lectura</th></tr></thead><tbody class="divide-y divide-line">
            @for (evento of actividad(); track evento.fecha_hora + evento.placa) {
              <tr [class.bg-amber-50]="esBajaConfianza(evento)"><td class="whitespace-nowrap px-5 py-3 font-mono font-semibold text-ink">{{ evento.placa }}</td><td class="px-5 py-3 text-ink">{{ evento.tipo_vehiculo }}</td><td class="px-5 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold" [class.bg-emerald-50]="evento.tipo === 'ingreso'" [class.text-emerald-800]="evento.tipo === 'ingreso'" [class.bg-blue-50]="evento.tipo === 'salida'" [class.text-blue-800]="evento.tipo === 'salida'">{{ evento.tipo }}</span></td><td class="px-5 py-3 text-ink">{{ evento.carril }}</td><td class="whitespace-nowrap px-5 py-3 text-muted">{{ evento.fecha_hora | date:'short' }}</td><td class="px-5 py-3"><span class="text-xs font-medium" [class.text-amber-800]="esBajaConfianza(evento)" [class.text-muted]="!esBajaConfianza(evento)">{{ evento.lectura_manual ? 'Manual' : (evento.confianza === null ? 'Automática' : evento.confianza + '% confianza') }}</span>@if (esBajaConfianza(evento)) { <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-900">BAJA</span> }</td></tr>
            }
          </tbody></table></div>
        }
      </section>

      <div class="grid gap-5 xl:grid-cols-[0.9fr_1.1fr]">
        <section aria-labelledby="baja-confianza-heading" class="rounded-2xl border border-line bg-surface shadow-card">
          <div class="border-b border-line px-5 py-4"><h2 id="baja-confianza-heading" class="font-heading text-lg font-semibold text-ink">Lecturas de baja confianza</h2><p class="mt-1 text-xs text-muted">Requieren revisión del operador.</p></div>
          @if (cargandoResumen()) { <p class="p-5 text-sm text-muted" role="status">Cargando lecturas…</p> }
          @else if (errorResumen()) { <div class="p-5 text-sm text-danger" role="alert">{{ errorResumen() }} <button class="font-semibold underline" (click)="actualizar()">Reintentar</button></div> }
          @else if (lecturasBajaConfianza().length === 0) { <p class="p-5 text-sm text-muted">No hay lecturas recientes por debajo del umbral.</p> }
          @else { <ul class="divide-y divide-line">@for (lectura of lecturasBajaConfianza(); track lectura.fecha_hora + lectura.placa) { <li class="flex items-center justify-between gap-3 px-5 py-3"><div><p class="font-mono text-sm font-semibold text-ink">{{ lectura.placa }}</p><p class="mt-0.5 text-xs text-muted">{{ lectura.fecha_hora | date:'short' }}</p></div><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-900">{{ lectura.confianza }}% confianza</span></li> }</ul> }
        </section>

        @if (esAdministrador()) {
          <section aria-labelledby="comparativo-heading" class="rounded-2xl border border-line bg-surface shadow-card">
            <div class="border-b border-line px-5 py-4"><h2 id="comparativo-heading" class="font-heading text-lg font-semibold text-ink">Comparativo entre sedes</h2><p class="mt-1 text-xs text-muted">Ocupación actual y recaudo del periodo.</p></div>
            @if (cargandoComparativo()) { <p class="p-5 text-sm text-muted" role="status">Cargando comparativo…</p> }
            @else if (errorComparativo()) { <div class="p-5 text-sm text-danger" role="alert">{{ errorComparativo() }} <button class="font-semibold underline" (click)="actualizar()">Reintentar</button></div> }
            @else if (comparativo().length === 0) { <p class="p-5 text-sm text-muted">No hay sedes para comparar.</p> }
            @else { <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-canvas text-[11px] uppercase tracking-wide text-muted"><tr><th class="px-5 py-3">Sede</th><th class="px-5 py-3">Ocupación</th><th class="px-5 py-3">Recaudo</th><th class="px-5 py-3">Pendiente</th><th class="px-5 py-3">Capacidad</th></tr></thead><tbody class="divide-y divide-line">@for (fila of comparativo(); track fila.sede.id) { <tr><td class="px-5 py-3 font-medium text-ink">{{ fila.sede.nombre }}</td><td class="px-5 py-3 text-ink">{{ fila.dentro }}/{{ fila.total }} · {{ fila.porcentaje_ocupacion }}%</td><td class="whitespace-nowrap px-5 py-3 text-ink">{{ fila.recaudo | currency:'COP':'symbol-narrow':'1.0-0' }}</td><td class="whitespace-nowrap px-5 py-3 text-ink">{{ fila.pendientes.valor | currency:'COP':'symbol-narrow':'1.0-0' }} <span class="text-xs text-muted">({{ fila.pendientes.cantidad }})</span></td><td class="px-5 py-3">@if (fila.alerta_capacidad) { <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-900">ALERTA</span> } @else { <span class="text-xs text-muted">Normal</span> }</td></tr> }</tbody></table></div> }
          </section>
        }
      </div>
      }
    </div>
  `,
})
export class DashboardComponent {
  private readonly dashboard = inject(DashboardService);
  private readonly auth = inject(AUTH_SERVICE);
  private readonly destroyRef = inject(DestroyRef);
  private readonly recargas = new Subject<void>();

  protected readonly sedes = signal<readonly SedeDashboard[]>([]);
  protected readonly sedeId = signal<number | null>(null);
  protected readonly hoyOperativo = signal('');
  protected readonly opcionPeriodo = signal<OpcionPeriodoDashboard>('hoy');
  protected readonly desdePersonalizado = signal('');
  protected readonly hastaPersonalizado = signal('');
  protected readonly periodo = computed(() => resolverPeriodoDashboard(
    this.opcionPeriodo(),
    this.hoyOperativo(),
    { desde: this.desdePersonalizado(), hasta: this.hastaPersonalizado() },
  ));
  protected readonly esAdministrador = signal(false);
  protected readonly resumen = signal<ResumenDashboard | null>(null);
  protected readonly actividad = signal<readonly EventoDashboard[]>([]);
  protected readonly comparativo = signal<readonly SedeComparativoDashboard[]>([]);
  protected readonly errorCatalogo = signal('');
  protected readonly errorResumen = signal('');
  protected readonly errorActividad = signal('');
  protected readonly errorComparativo = signal('');
  protected readonly cargandoCatalogo = signal(true);
  protected readonly cargandoResumen = signal(true);
  protected readonly cargandoActividad = signal(true);
  protected readonly cargandoComparativo = signal(false);

  protected lecturasBajaConfianza = computed<readonly LecturaBajaConfianzaDashboard[]>(
    () => this.resumen()?.lecturas_manuales.recientes_baja_confianza ?? [],
  );

  constructor() {
    this.auth.currentUser().pipe(takeUntilDestroyed(this.destroyRef)).subscribe(user => {
      this.esAdministrador.set(user?.rol === 'administrador');
      if (this.sedeId() !== null) this.actualizar();
    });

    this.recargas.pipe(
      switchMap(() => this.consultarBloques()),
      takeUntilDestroyed(this.destroyRef),
    ).subscribe(({ resumen, actividad, comparativo }) => {
      this.resumen.set(resumen.datos);
      this.errorResumen.set(resumen.error ?? '');
      this.cargandoResumen.set(false);
      this.actividad.set(actividad.datos ?? []);
      this.errorActividad.set(actividad.error ?? '');
      this.cargandoActividad.set(false);
      if (this.esAdministrador()) {
        this.comparativo.set(comparativo.datos ?? []);
        this.errorComparativo.set(comparativo.error ?? '');
        this.cargandoComparativo.set(false);
      }
    });

    interval(30_000).pipe(takeUntilDestroyed(this.destroyRef)).subscribe(() => this.actualizar());
    this.cargarCatalogo();
  }

  protected cambiarSede(valor: number | string | null): void {
    const id = Number(valor);
    this.sedeId.set(Number.isFinite(id) && id > 0 ? id : null);
    this.actualizar();
  }

  protected cambiarOpcionPeriodo(valor: string): void {
    if (!['hoy', 'ayer', '7dias', 'rango'].includes(valor)) return;
    const opcion = valor as OpcionPeriodoDashboard;
    this.opcionPeriodo.set(opcion);
    if (opcion === 'rango') {
      this.desdePersonalizado.set(this.desdePersonalizado() || this.hoyOperativo());
      this.hastaPersonalizado.set(this.hastaPersonalizado() || this.hoyOperativo());
    }
    this.actualizar();
  }

  protected cambiarDesde(valor: string): void {
    this.desdePersonalizado.set(valor);
    this.opcionPeriodo.set('rango');
    this.actualizar();
  }

  protected cambiarHasta(valor: string): void {
    this.hastaPersonalizado.set(valor);
    this.opcionPeriodo.set('rango');
    this.actualizar();
  }

  protected actualizar(): void {
    if (this.sedeId() === null) return;
    this.cargandoResumen.set(true);
    this.cargandoActividad.set(true);
    this.errorResumen.set('');
    this.errorActividad.set('');
    if (this.esAdministrador()) {
      this.cargandoComparativo.set(true);
      this.errorComparativo.set('');
    }
    this.recargas.next();
  }

  protected reintentarCatalogo(): void { this.cargarCatalogo(); }

  protected esBajaConfianza(evento: EventoDashboard): boolean {
    return this.lecturasBajaConfianza().some(lectura => lectura.placa === evento.placa && lectura.fecha_hora === evento.fecha_hora);
  }

  protected formatearDinero(valor: number): string {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(valor);
  }

  private cargarCatalogo(): void {
    this.cargandoCatalogo.set(true);
    this.errorCatalogo.set('');
    this.dashboard.obtenerSedes().pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: (catalogo: CatalogoDashboard) => {
        this.sedes.set(catalogo.sedes);
        this.hoyOperativo.set(catalogo.hoy_operativo);
        this.desdePersonalizado.set(catalogo.hoy_operativo);
        this.hastaPersonalizado.set(catalogo.hoy_operativo);
        if (this.sedeId() === null && catalogo.sedes.length > 0) this.sedeId.set(catalogo.sedes[0].id);
        this.cargandoCatalogo.set(false);
        this.actualizar();
      },
      error: (error: unknown) => {
        this.cargandoCatalogo.set(false);
        this.cargandoResumen.set(false);
        this.cargandoActividad.set(false);
        this.cargandoComparativo.set(false);
        if (error instanceof HttpErrorResponse && error.status === 401) {
          this.errorCatalogo.set('El API respondió 401. Reinicia Laravel para aplicar las rutas temporales sin autenticación.');
        } else if (error instanceof HttpErrorResponse && error.status === 0) {
          this.errorCatalogo.set('No hay conexión con la API. Comprueba que Laravel esté activo en la URL configurada.');
        } else {
          this.errorCatalogo.set('La API no pudo entregar el catálogo de sedes. Revisa el backend e intenta de nuevo.');
        }
      },
    });
  }

  private consultarBloques(): Observable<{
    resumen: ResultadoBloque<ResumenDashboard>;
    actividad: ResultadoBloque<EventoDashboard[]>;
    comparativo: ResultadoBloque<SedeComparativoDashboard[]>;
  }> {
    const sede = this.sedeId();
    if (sede === null) {
      return of({
        resumen: { datos: null, error: 'Selecciona una sede.' },
        actividad: { datos: [], error: null },
        comparativo: { datos: [], error: null },
      });
    }
    const periodo = this.periodo();
    return forkJoin({
      resumen: this.comoBloque(this.dashboard.obtenerResumen(sede, periodo), 'No fue posible cargar el resumen.'),
      actividad: this.comoBloque(this.dashboard.obtenerActividad(sede), 'No fue posible cargar la actividad.'),
      comparativo: this.esAdministrador()
        ? this.comoBloque(this.dashboard.obtenerComparativo(periodo), 'No fue posible cargar el comparativo.')
        : of({ datos: [], error: null } as ResultadoBloque<SedeComparativoDashboard[]>),
    });
  }

  private comoBloque<T>(fuente: Observable<T>, mensajeError: string): Observable<ResultadoBloque<T>> {
    return fuente.pipe(
      map(datos => ({ datos, error: null })),
      catchError(() => of({ datos: null, error: mensajeError })),
    );
  }
}
