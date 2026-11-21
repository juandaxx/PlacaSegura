import { Component, input } from '@angular/core';

@Component({
  selector: 'app-info-row',
  template: `<div class="flex items-start gap-3"><span class="grid size-9 shrink-0 place-items-center rounded-lg bg-canvas text-sm text-muted" aria-hidden="true">{{ icon() }}</span><div class="min-w-0"><p class="text-xs text-muted">{{ label() }}</p><p class="mt-1 break-words text-sm font-medium text-ink">{{ value() }}</p></div></div>`,
})
export class InfoRowComponent {
  readonly icon = input('•');
  readonly label = input.required<string>();
  readonly value = input.required<string>();
}
