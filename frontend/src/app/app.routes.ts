import { Routes } from '@angular/router';
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
  { path: '**', redirectTo: 'login' },
];
