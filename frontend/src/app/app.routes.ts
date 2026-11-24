import { Routes } from '@angular/router';
import { authGuard, roleGuard } from './core/auth/route.guards';
import { DashboardLayoutComponent } from './layout/dashboard-layout.component';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login/login.component').then(module => module.LoginComponent),
  },
  { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
  {
    path: 'dashboard',
    component: DashboardLayoutComponent,
    children: [
      { path: '', loadComponent: () => import('./features/dashboard/dashboard.component').then(module => module.DashboardComponent) },
    ],
  },
  {
    path: 'entrada',
    component: DashboardLayoutComponent,
    canActivate: [authGuard, roleGuard],
    data: { roles: ['operador', 'administrador'] },
    children: [
      { path: '', loadComponent: () => import('./features/operador/carril/entrada.component').then(module => module.EntradaComponent) },
    ],
  },
  { path: '**', redirectTo: 'login' },
];
