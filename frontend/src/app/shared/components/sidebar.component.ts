import { Component, input, output } from '@angular/core';
import { NgTemplateOutlet } from '@angular/common';

interface NavigationItem {
  readonly label: string;
  readonly icon: string;
  readonly available: boolean;
}

@Component({
  selector: 'app-sidebar',
  template: `
    @if (open()) {
      <button type="button" aria-label="Cerrar menú" class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" (click)="closeMenu.emit()"></button>
    }
    <aside class="fixed inset-y-0 left-0 z-40 flex w-[264px] flex-col bg-sidebar text-white transition-transform lg:sticky lg:top-0 lg:h-dvh lg:translate-x-0"
      [class.translate-x-0]="open()" [class.-translate-x-full]="!open()">
      <div class="flex h-[84px] items-center gap-3 border-b border-white/10 px-6">
        <span class="grid size-10 place-items-center rounded-xl bg-white/10 text-lg font-bold text-white" aria-hidden="true">PS</span>
        <div><p class="font-heading text-base font-semibold leading-5">Placa Segura</p><p class="mt-1 text-xs tracking-wide text-sidebar-muted">S.A.S.</p></div>
        <button type="button" aria-label="Cerrar menú" class="ml-auto rounded-lg p-2 text-sidebar-muted hover:bg-white/10 lg:hidden" (click)="closeMenu.emit()">×</button>
      </div>
      <nav aria-label="Navegación principal" class="flex-1 space-y-1 overflow-y-auto px-3 py-6">
        <p class="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-sidebar-muted">Operación</p>
        @for (item of operationItems; track item.label) { <ng-container *ngTemplateOutlet="navItem; context: { $implicit: item }" /> }
        <p class="mb-3 mt-8 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-sidebar-muted">Administración</p>
        @for (item of adminItems; track item.label) { <ng-container *ngTemplateOutlet="navItem; context: { $implicit: item }" /> }
      </nav>
      <div class="border-t border-white/10 px-6 py-4 text-xs text-sidebar-muted">Sistema de parqueadero</div>
    </aside>
    <ng-template #navItem let-item>
      <button type="button" [disabled]="!item.available" [attr.aria-disabled]="!item.available"
        class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-sidebar-muted transition hover:bg-white/5 hover:text-white disabled:cursor-default disabled:opacity-75">
        <span class="grid size-5 place-items-center text-base" aria-hidden="true">{{ item.icon }}</span><span>{{ item.label }}</span>
      </button>
    </ng-template>
  `,
  imports: [NgTemplateOutlet],
})
export class SidebarComponent {
  readonly open = input(false);
  readonly closeMenu = output<void>();
  protected readonly operationItems: NavigationItem[] = [
    { label: 'Carril de entrada', icon: '↘', available: false },
    { label: 'Carril de salida', icon: '↗', available: false },
    { label: 'Cupos', icon: '▦', available: false },
    { label: 'Portal de pagos', icon: '$', available: false },
  ];
  protected readonly adminItems: NavigationItem[] = [
    { label: 'Tarifas', icon: '◷', available: false },
    { label: 'Eventos y cobros', icon: '▤', available: false },
    { label: 'Usuarios', icon: '♙', available: false },
    { label: 'Configuración', icon: '⚙', available: false },
  ];
}
