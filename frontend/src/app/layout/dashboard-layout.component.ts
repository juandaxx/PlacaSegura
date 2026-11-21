import { Component, signal } from '@angular/core';
import { SidebarComponent } from '../shared/components/sidebar.component';
import { TopbarComponent } from '../shared/components/topbar.component';

@Component({
  selector: 'app-dashboard-layout',
  imports: [SidebarComponent, TopbarComponent],
  template: `
    <div class="min-h-dvh lg:flex">
      <app-sidebar [open]="menuOpen()" (closeMenu)="menuOpen.set(false)" />
      <div class="min-w-0 flex-1">
        <app-topbar (menuClick)="menuOpen.update(open => !open)" />
        <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
          <section class="rounded-card border border-line bg-surface p-6 shadow-card sm:p-8">
            <p class="font-heading text-xl font-semibold text-ink">Placa Segura S.A.S.</p>
            <p class="mt-2 text-sm leading-6 text-muted">La estructura de navegación está lista. Las pantallas se incorporarán en las siguientes etapas.</p>
          </section>
        </main>
      </div>
    </div>
  `,
})
export class DashboardLayoutComponent {
  protected readonly menuOpen = signal(false);
}
