import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { take } from 'rxjs';
import { AUTH_SERVICE } from '../../../core/api/parking.services';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule],
  template: `
    <main class="relative isolate flex min-h-dvh items-center justify-center overflow-hidden bg-canvas px-4 py-8 sm:px-8 lg:px-12">
      <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
        <div class="absolute -right-36 -top-40 size-[30rem] rounded-full bg-blue-100/60 blur-3xl"></div>
        <div class="absolute -bottom-48 -left-40 size-[32rem] rounded-full bg-slate-200/70 blur-3xl"></div>
      </div>

      <div class="grid w-full max-w-[1120px] overflow-hidden rounded-[24px] border border-line bg-surface shadow-[0_24px_80px_rgba(16,42,67,0.14)] lg:min-h-[680px] lg:grid-cols-[1.04fr_0.96fr]">
        <aside class="relative hidden flex-col justify-between overflow-hidden bg-sidebar px-12 py-11 text-white lg:flex xl:px-16">
          <div aria-hidden="true" class="absolute -right-24 -top-24 size-[26rem] rounded-full border border-white/10"></div>
          <div aria-hidden="true" class="absolute -right-10 -top-10 size-[18rem] rounded-full border border-white/10"></div>
          <div aria-hidden="true" class="absolute -bottom-32 -left-24 size-[30rem] rounded-full border border-white/10"></div>
          <div class="relative flex items-center gap-3">
            <span class="grid size-12 place-items-center rounded-xl bg-white/10 font-heading text-sm font-bold tracking-wide text-white">PS</span>
            <span><span class="block font-heading text-base font-semibold">Placa Segura</span><span class="mt-0.5 block text-xs tracking-[0.18em] text-sidebar-muted">S.A.S.</span></span>
          </div>

          <div class="relative max-w-md py-10">
            <div class="mb-8 grid size-20 place-items-center rounded-2xl border border-white/15 bg-white/10">
              <svg viewBox="0 0 48 48" class="size-11" fill="none" aria-hidden="true">
                <path d="M8 36V12a4 4 0 0 1 4-4h24a4 4 0 0 1 4 4v24H8Z" stroke="currentColor" stroke-width="2.5" />
                <path d="M17 35V16h8a6 6 0 0 1 0 12h-8m0-6h8M12 39h24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" />
              </svg>
            </div>
            <p class="font-heading text-3xl font-semibold leading-tight xl:text-[2.5rem]">Tu parqueadero, bajo control.</p>
            <p class="mt-4 max-w-sm text-sm leading-6 text-blue-100/80">Gestiona el ingreso, la salida y la ocupación desde un solo lugar.</p>
          </div>

          <div class="relative flex items-center gap-3 text-xs text-sidebar-muted">
            <span class="h-px w-8 bg-sidebar-muted/70"></span>
            <span>Sistema de gestión de parqueadero</span>
          </div>
          <div aria-hidden="true" class="absolute bottom-24 right-0 h-px w-40 bg-white/10"></div>
          <div aria-hidden="true" class="absolute bottom-20 right-10 h-24 w-px bg-white/10"></div>
        </aside>

        <section class="flex items-center justify-center px-6 py-10 sm:px-12 lg:px-14 xl:px-20">
          <div class="w-full max-w-[392px]">
            <div class="mb-9 flex items-center gap-3 lg:hidden">
              <span class="grid size-11 place-items-center rounded-xl bg-sidebar font-heading text-sm font-bold text-white">PS</span>
              <span><span class="block font-heading text-sm font-semibold text-ink">Placa Segura</span><span class="text-[11px] tracking-[0.15em] text-muted">S.A.S.</span></span>
            </div>

            <div class="mb-8">
              <span class="mb-5 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-primary">
                <span class="grid size-5 place-items-center rounded-full bg-primary/10" aria-hidden="true">♙</span>
                Operador
              </span>
              <h1 class="font-heading text-2xl font-semibold tracking-tight text-ink sm:text-[1.75rem]">Iniciar sesión</h1>
              <p class="mt-2 text-sm leading-6 text-muted">Ingresa tus datos para continuar al sistema.</p>
            </div>

            <form class="space-y-5" [formGroup]="form" (ngSubmit)="submit()" novalidate>
              <div>
                <label for="username" class="mb-2 block text-sm font-medium text-ink">Usuario</label>
                <input id="username" type="text" formControlName="username" autocomplete="username" placeholder="Ingresa tu usuario"
                  [attr.aria-invalid]="usernameInvalid" [attr.aria-describedby]="usernameInvalid ? 'username-error' : null"
                  class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink placeholder:text-slate-400 transition focus:border-primary focus:outline-none focus:ring-4 focus:ring-blue-100" />
                @if (usernameInvalid) { <p id="username-error" class="mt-1.5 text-xs font-medium text-danger">Ingresa tu usuario.</p> }
              </div>

              <div>
                <label for="password" class="mb-2 block text-sm font-medium text-ink">Contraseña</label>
                <input id="password" type="password" formControlName="password" autocomplete="current-password" placeholder="Ingresa tu contraseña"
                  [attr.aria-invalid]="passwordInvalid" [attr.aria-describedby]="passwordInvalid ? 'password-error' : null"
                  class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink placeholder:text-slate-400 transition focus:border-primary focus:outline-none focus:ring-4 focus:ring-blue-100" />
                @if (passwordInvalid) { <p id="password-error" class="mt-1.5 text-xs font-medium text-danger">La contraseña debe tener al menos 6 caracteres.</p> }
              </div>

              @if (errorMessage()) {
                <p class="rounded-lg border border-red-200 bg-red-50 px-3.5 py-3 text-sm text-danger" role="alert">{{ errorMessage() }}</p>
              }
              <button type="submit" [disabled]="isSubmitting()" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong focus-visible:outline-offset-4 disabled:cursor-wait disabled:opacity-70">
                @if (isSubmitting()) { <span class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span> }
                {{ isSubmitting() ? 'Ingresando…' : 'Ingresar' }}
              </button>
            </form>

            <p class="mt-8 text-center text-xs leading-5 text-muted">Acceso exclusivo para personal autorizado.</p>
          </div>
        </section>
      </div>
    </main>
  `,
})
export class LoginComponent {
  private readonly formBuilder = inject(FormBuilder);
  private readonly authService = inject(AUTH_SERVICE);
  private readonly router = inject(Router);

  protected readonly form = this.formBuilder.nonNullable.group({
    username: ['', Validators.required],
    password: ['', [Validators.required, Validators.minLength(6)]],
  });
  protected readonly isSubmitting = signal(false);
  protected readonly errorMessage = signal('');

  protected get usernameInvalid(): boolean {
    return this.form.controls.username.invalid && this.form.controls.username.touched;
  }

  protected get passwordInvalid(): boolean {
    return this.form.controls.password.invalid && this.form.controls.password.touched;
  }

  protected submit(): void {
    this.errorMessage.set('');
    this.form.markAllAsTouched();
    if (this.form.invalid) return;

    this.isSubmitting.set(true);
    this.authService.signIn(this.form.getRawValue()).pipe(take(1)).subscribe({
      next: user => {
        this.isSubmitting.set(false);
        if (!user) {
          this.errorMessage.set('No fue posible iniciar sesión con esos datos.');
          return;
        }
        void this.router.navigateByUrl('/');
      },
      error: () => {
        this.isSubmitting.set(false);
        this.errorMessage.set('Ocurrió un error al iniciar sesión. Intenta nuevamente.');
      },
    });
  }
}
