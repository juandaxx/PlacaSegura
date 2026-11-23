import { Component, input } from '@angular/core';

@Component({
  selector: 'app-indicator-card',
  template: `
    <article class="rounded-card border border-line bg-surface p-5 shadow-card">
      <div class="flex items-start justify-between gap-3">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ label() }}</p>
          <p class="mt-2 font-heading text-2xl font-semibold text-ink">{{ value() }}</p>
          @if (detail()) { <p class="mt-1 text-xs text-muted">{{ detail() }}</p> }
        </div>
        <span class="grid size-10 place-items-center rounded-xl bg-blue-50 text-lg font-semibold text-primary" aria-hidden="true">{{ icon() }}</span>
      </div>
    </article>
  `,
})
export class IndicatorCardComponent {
  readonly label = input.required<string>();
  readonly value = input.required<string>();
  readonly detail = input('');
  readonly icon = input('•');
}
