import { Component, input } from '@angular/core';

@Component({
  selector: 'app-plate-badge',
  template: `<span class="inline-flex items-center rounded-lg border border-slate-300 bg-slate-50 px-3 py-1.5 font-mono text-sm font-bold tracking-[0.12em] text-ink" [attr.aria-label]="'Placa ' + plate()">{{ plate() }}</span>`,
})
export class PlateBadgeComponent {
  readonly plate = input.required<string>();
}
