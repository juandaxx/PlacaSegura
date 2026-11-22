import { Routes } from '@angular/router';
import { authGuard } from './core/auth/route.guards';
import { DashboardLayoutComponent } from './layout/dashboard-layout.component';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login/login.component').then(module => module.LoginComponent),
  },
  { path: '', pathMatch: 'full', component: DashboardLayoutComponent, canActivate: [authGuard] },
  { path: '**', redirectTo: 'login' },
];
