import { HttpInterceptorFn } from '@angular/common/http';
import { InjectionToken, inject } from '@angular/core';

export const AUTH_TOKEN = new InjectionToken<string | null>('AUTH_TOKEN', { factory: () => null });

export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const token = inject(AUTH_TOKEN);
  return next(token ? request.clone({ setHeaders: { Authorization: `Bearer ${token}` } }) : request);
};
