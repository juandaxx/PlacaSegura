import { Component, input } from '@angular/core';

@Component({
  selector: 'app-occupancy-card',
  template: `
    <article class="rounded-card border border-line bg-surface p-5 shadow-card"
      title="La ocupacion es una estimacion derivada de los eventos de acceso.">
      <div class="flex items-start justify-between gap-3">
        <div>
          <p class="font-heading text-base font-semibold text-ink">{{ title() }}</p>
          <p class="mt-1 text-xs text-muted">{{ occupied() }} dentro de {{ total() }} cupos· {{ available() }} disponibles</p>
        </div>
        <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
          [class.bg-red-50]="isFull() || isOverCapacity()" [class.text-danger]="isFull() || isOverCapacity()"
          [class.bg-amber-50]="isNearLimit()" [class.text-amber-800]="isNearLimit()"
          [class.bg-emerald-50]="!isFull() && !isOverCapacity() && !isNearLimit()"
          [class.text-success]="!isFull() && !isOverCapacity() && !isNearLimit()">
          {{ statusLabel() }}
        </span>
      </div>
      <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar"
        [attr.aria-valuenow]="barPercentage()" aria-valuemin="0" aria-valuemax="100"
        [attr.aria-label]="'OcupaciÃ³n estimada de ' + title()">
        <div class="h-full rounded-full transition-[width]" [class.bg-red-600]="isFull() || isOverCapacity()"
          [class.bg-amber-500]="isNearLimit()" [class.bg-primary]="!isFull() && !isOverCapacity() && !isNearLimit()"
          [style.width.%]="barPercentage()"></div>
      </div>
      <p class="mt-2 text-right text-xs text-muted">{{ percentage() }}% ocupado· Estimacion</p>
    </article>
  `,
})
export class OccupancyCardComponent {
  readonly title = input.required<string>();
  readonly occupied = input.required<number>();
  readonly total = input.required<number>();
  readonly alertThreshold = input(90);

  protected available(): number { return Math.max(0, this.total() - this.occupied()); }
  protected percentage(): number { return this.total() > 0 ? Math.round(this.occupied() / this.total() * 1000) / 10 : 0; }
  protected barPercentage(): number { return Math.min(100, this.percentage()); }
  protected isOverCapacity(): boolean { return this.total() > 0 && this.occupied() > this.total(); }
  protected isFull(): boolean { return this.total() > 0 && this.available() === 0 && !this.isOverCapacity(); }
  protected isNearLimit(): boolean { return !this.isFull() && !this.isOverCapacity() && this.percentage() >= this.alertThreshold(); }

  protected statusLabel(): string {
    if (this.isOverCapacity()) return 'LLENO· SOBRECUPO';
    if (this.isFull()) return 'LLENO';
    if (this.isNearLimit()) return 'Cerca del li­mite';
    return 'Normal';
  }
}
