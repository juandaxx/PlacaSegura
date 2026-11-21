import { Component, output } from '@angular/core';

@Component({
  selector: 'app-topbar',
  template: `
    <header class="flex h-[84px] items-center justify-between border-b border-line bg-surface px-4 sm:px-6 lg:px-8">
      <div class="flex items-center gap-3">
        <button type="button" class="grid size-10 place-items-center rounded-lg border border-line text-xl text-ink hover:bg-canvas lg:hidden" aria-label="Abrir menú" (click)="menuClick.emit()">☰</button>
        <div class="hidden sm:block"><p class="font-heading text-sm font-semibold text-ink">Panel de control</p><p class="mt-1 text-xs text-muted">Sistema de gestión de parqueadero</p></div>
      </div>
      <div class="flex items-center gap-3 sm:gap-5">
        <label class="hidden items-center gap-2 text-xs font-medium text-muted sm:flex">Sede
          <select aria-label="Sede actual" class="rounded-lg border border-line bg-surface px-3 py-2 text-sm font-medium text-ink"><option>Sede principal</option></select>
        </label>
        <span class="hidden h-9 w-px bg-line sm:block" aria-hidden="true"></span>
        <div class="flex items-center gap-2.5"><span class="grid size-9 place-items-center rounded-full bg-blue-50 text-sm font-semibold text-primary" aria-hidden="true">OP</span><div class="hidden sm:block"><p class="text-sm font-semibold text-ink">Operador</p><p class="text-xs text-muted">Sesión de demostración</p></div></div>
      </div>
    </header>
  `,
})
export class TopbarComponent {
  readonly menuClick = output<void>();
}
