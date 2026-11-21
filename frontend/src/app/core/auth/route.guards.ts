import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { map } from 'rxjs';
import { AUTH_SERVICE } from '../api/parking.services';
import { UserRole } from '../models/parking.models';

export const authGuard: CanActivateFn = (_route, state) => {
  const router = inject(Router);
  return inject(AUTH_SERVICE).currentUser().pipe(
    map(user => user ? true : router.createUrlTree(['/login'], { queryParams: { returnUrl: state.url } })),
  );
};

export const roleGuard: CanActivateFn = route => {
  const router = inject(Router);
  const allowedRoles = route.data['roles'] as readonly UserRole[] | undefined;
  return inject(AUTH_SERVICE).currentUser().pipe(
    map(user => user && (!allowedRoles?.length || allowedRoles.includes(user.rol))
      ? true
      : router.createUrlTree(['/login'])),
  );
};
