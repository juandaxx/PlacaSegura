import { Component, input } from '@angular/core';

export type StatusKind = 'success' | 'warning' | 'danger' | 'neutral';

@Component({
  selector: 'app-status-banner',
  template: `<div class="flex items-center gap-3 rounded-xl border p-4" [class.border-emerald-200]="kind() === 'success'" [class.bg-emerald-50]="kind() === 'success'" [class.text-emerald-800]="kind() === 'success'" [class.border-amber-200]="kind() === 'warning'" [class.bg-amber-50]="kind() === 'warning'" [class.text-amber-900]="kind() === 'warning'" [class.border-red-200]="kind() === 'danger'" [class.bg-red-50]="kind() === 'danger'" [class.text-red-800]="kind() === 'danger'" [class.border-slate-200]="kind() === 'neutral'" [class.bg-slate-50]="kind() === 'neutral'" [class.text-slate-700]="kind() === 'neutral'" role="status"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-white/70" aria-hidden="true">{{ icon() }}</span><div><p class="text-sm font-semibold">{{ title() }}</p>@if (description()) { <p class="mt-0.5 text-xs opacity-80">{{ description() }}</p> }</div></div>`,
})
export class StatusBannerComponent {
  readonly kind = input<StatusKind>('neutral');
  readonly title = input.required<string>();
  readonly description = input('');
  readonly icon = input('i');
}
