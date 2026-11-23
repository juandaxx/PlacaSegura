import { Component, output } from '@angular/core';

@Component({
  selector: 'app-topbar',
  template: `
    <header class="flex h-[84px] items-center justify-between border-b border-line bg-surface px-4 sm:px-6 lg:px-8">
      <div class="flex items-center gap-3">
        <button type="button" class="grid size-10 place-items-center rounded-lg border border-line text-xl text-ink hover:bg-canvas lg:hidden" aria-label="Abrir menú" (click)="menuClick.emit()">☰</button>
        <div><p class="font-heading text-sm font-semibold text-ink">Panel de control</p><p class="mt-1 text-xs text-muted">Sistema de gestión de parqueadero</p></div>
      </div>
      <span class="hidden rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-primary sm:inline-flex">Placa Segura</span>
    </header>
  `,
})
export class TopbarComponent {
  readonly menuClick = output<void>();
}
