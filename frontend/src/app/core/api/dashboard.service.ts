import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import {
  CatalogoDashboard,
  EventoDashboard,
  FiltroPeriodoDashboard,
  ResumenDashboard,
  SedeComparativoDashboard,
} from '../models/dashboard.models';

@Injectable({ providedIn: 'root' })
export class DashboardService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = `${environment.apiUrl}/dashboard`;

  obtenerSedes(): Observable<CatalogoDashboard> {
    return this.http.get<CatalogoDashboard>(`${this.apiUrl}/sedes`);
  }

  obtenerResumen(sedeId: number, periodo: FiltroPeriodoDashboard): Observable<ResumenDashboard> {
    const params = new HttpParams()
      .set('sede_id', sedeId)
      .set('desde', periodo.desde)
      .set('hasta', periodo.hasta);
    return this.http.get<ResumenDashboard>(`${this.apiUrl}/resumen`, { params });
  }

  obtenerActividad(sedeId: number, limite = 10): Observable<EventoDashboard[]> {
    const params = new HttpParams().set('sede_id', sedeId).set('limite', limite);
    return this.http.get<EventoDashboard[]>(`${this.apiUrl}/actividad`, { params });
  }

  obtenerComparativo(periodo: FiltroPeriodoDashboard): Observable<SedeComparativoDashboard[]> {
    const params = new HttpParams().set('desde', periodo.desde).set('hasta', periodo.hasta);
    return this.http.get<SedeComparativoDashboard[]>(`${this.apiUrl}/comparativo-sedes`, { params });
  }
}
