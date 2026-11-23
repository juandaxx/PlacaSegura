import { Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { SidebarComponent } from '../shared/components/sidebar.component';
import { TopbarComponent } from '../shared/components/topbar.component';

@Component({
  selector: 'app-dashboard-layout',
  imports: [SidebarComponent, TopbarComponent, RouterOutlet],
  template: `
    <div class="min-h-dvh lg:flex">
      <app-sidebar [open]="menuOpen()" (closeMenu)="menuOpen.set(false)" />
      <div class="min-w-0 flex-1">
        <app-topbar (menuClick)="menuOpen.update(open => !open)" />
        <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8"><router-outlet /></main>
      </div>
    </div>
  `,
})
export class DashboardLayoutComponent {
  protected readonly menuOpen = signal(false);
}
