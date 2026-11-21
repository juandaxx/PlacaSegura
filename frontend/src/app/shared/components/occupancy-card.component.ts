import { Component, input } from '@angular/core';

@Component({
  selector: 'app-occupancy-card',
  template: `<article class="rounded-card border border-line bg-surface p-5 shadow-card"><div class="flex items-center justify-between gap-3"><div><p class="font-heading text-base font-semibold text-ink">{{ title() }}</p><p class="mt-1 text-xs text-muted">{{ occupied() }} de {{ total() }} ocupados</p></div><span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide" [class.bg-red-50]="isFull()" [class.text-danger]="isFull()" [class.bg-emerald-50]="!isFull()" [class.text-success]="!isFull()">{{ isFull() ? 'Lleno' : 'Disponible' }}</span></div><div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" [attr.aria-valuenow]="percentage()" aria-valuemin="0" aria-valuemax="100" [attr.aria-label]="'Ocupación de ' + title()"><div class="h-full rounded-full bg-primary transition-[width]" [style.width.%]="percentage()"></div></div><p class="mt-2 text-right text-xs text-muted">{{ percentage() }}% ocupado</p></article>`,
})
export class OccupancyCardComponent {
  readonly title = input.required<string>();
  readonly occupied = input.required<number>();
  readonly total = input.required<number>();
  protected percentage(): number { return this.total() > 0 ? Math.min(100, Math.round(this.occupied() / this.total() * 100)) : 0; }
  protected isFull(): boolean { return this.total() > 0 && this.occupied() >= this.total(); }
}
