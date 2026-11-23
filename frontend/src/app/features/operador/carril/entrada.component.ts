import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { forkJoin, take } from 'rxjs';
import { LANE_SERVICE } from '../../../core/api/parking.services';
import { EstadoBarrera, EventoCarril, VehicleType } from '../../../core/models/parking.models';
import { CameraPanelComponent } from '../../../shared/components/camera-panel.component';
import { InfoRowComponent } from '../../../shared/components/info-row.component';
import { PlateBadgeComponent } from '../../../shared/components/plate-badge.component';
import { StatusBannerComponent } from '../../../shared/components/status-banner.component';

@Component({
  selector: 'app-entrada',
  imports: [DatePipe, CameraPanelComponent, InfoRowComponent, PlateBadgeComponent, StatusBannerComponent],
  template: `
    <div class="space-y-6">
      <header>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Operación</p>
        <h1 class="mt-1 font-heading text-2xl font-semibold tracking-tight text-ink sm:text-3xl">Carril de entrada</h1>
        <p class="mt-2 text-sm text-muted">Supervisión de ingreso y lectura de placas.</p>
      </header>

      @if (error()) {
        <app-status-banner kind="danger" title="No se pudo cargar el estado del carril" [description]="error()!" icon="!" />
        <button type="button" class="text-sm font-semibold text-primary underline" (click)="cargarDatos()">Reintentar</button>
      } @else if (cargando()) {
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.85fr)]" aria-label="Cargando carril de entrada" role="status">
          <div class="aspect-video animate-pulse rounded-card bg-slate-200"></div>
          <div class="min-h-64 animate-pulse rounded-card bg-slate-200"></div>
        </div>
      } @else if (evento(); as currentEvent) {
        <section class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.85fr)]" aria-label="Estado del carril de entrada">
          <app-camera-panel
            [lane]="currentEvent.carrilNombre ?? 'Carril de entrada 1'"
            [site]="currentEvent.sedeNombre ?? 'Sede principal'"
            [timestamp]="(currentEvent.fecha | date:'dd/MM/yyyy · HH:mm:ss') ?? ''"
          />

          <article class="overflow-hidden rounded-card border border-line bg-surface shadow-card">
            <header class="border-b border-line px-5 py-4 sm:px-6">
              <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">Lectura más reciente</p>
              <h2 class="mt-1 font-heading text-lg font-semibold text-ink">Placa detectada</h2>
            </header>
            <div class="px-5 py-5 sm:px-6 sm:py-6">
              <app-plate-badge [plate]="currentEvent.placa" />
              <div class="mt-6 grid gap-4 border-t border-line pt-5 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                <app-info-row icon="▣" label="Tipo de vehículo" [value]="vehicleLabel(currentEvent.tipoVehiculo)" />
                <app-info-row icon="◷" label="Fecha" [value]="(currentEvent.fecha | date:'dd/MM/yyyy') ?? '—'" />
                <app-info-row icon="◷" label="Hora" [value]="(currentEvent.fecha | date:'HH:mm:ss') ?? '—'" />
                <app-info-row icon="⌖" label="Sede" [value]="currentEvent.sedeNombre ?? 'Sede principal'" />
                <app-info-row icon="↘" label="Carril" [value]="currentEvent.carrilNombre ?? 'Carril de entrada 1'" />
              </div>
            </div>
          </article>
        </section>

        @if (estadoBarrera(); as barrier) {
          <app-status-banner
            [kind]="barrier.abierta ? 'success' : 'danger'"
            [title]="barrier.abierta ? 'Barrera abierta' : 'Barrera cerrada'"
            [description]="barrier.abierta ? 'Ingreso autorizado.' : (barrier.motivo ?? 'El ingreso está detenido.')"
            [icon]="barrier.abierta ? '✓' : '!'"
          />
        }
      }
    </div>
  `,
})
export class EntradaComponent {
  private readonly laneService = inject(LANE_SERVICE);
  protected readonly evento = signal<EventoCarril | null>(null);
  protected readonly estadoBarrera = signal<EstadoBarrera | null>(null);
  protected readonly cargando = signal(true);
  protected readonly error = signal('');

  constructor() { this.cargarDatos(); }

  protected vehicleLabel(type?: VehicleType): string {
    if (type === 'carro') return 'Automóvil';
    if (type === 'moto') return 'Motocicleta';
    return 'Sin identificar';
  }

  protected cargarDatos(): void {
    this.cargando.set(true);
    this.error.set('');
    forkJoin({
      evento: this.laneService.latestEvent('entrada'),
      barrera: this.laneService.barrierState('entrada'),
    }).pipe(take(1)).subscribe({
      next: ({ evento, barrera }) => {
        this.evento.set(evento);
        this.estadoBarrera.set(barrera);
        this.cargando.set(false);
      },
      error: () => {
        this.error.set('Intenta cargar nuevamente la información del carril.');
        this.cargando.set(false);
      },
    });
  }
}
