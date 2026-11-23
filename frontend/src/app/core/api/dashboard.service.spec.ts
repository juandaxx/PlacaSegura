import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { DashboardService } from './dashboard.service';

describe('DashboardService', () => {
  let service: DashboardService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({ providers: [provideHttpClient(), provideHttpClientTesting()] });
    service = TestBed.inject(DashboardService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('consulta el resumen con sede y rango en la URL configurada', () => {
    service.obtenerResumen(4, { desde: '2026-11-20', hasta: '2026-11-21' }).subscribe();
    const request = http.expectOne(request => request.url.endsWith('/dashboard/resumen'));
    expect(request.request.method).toBe('GET');
    expect(request.request.params.get('sede_id')).toBe('4');
    expect(request.request.params.get('desde')).toBe('2026-11-20');
    expect(request.request.params.get('hasta')).toBe('2026-11-21');
    request.flush({});
  });
});
