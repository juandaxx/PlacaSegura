import { InjectionToken, Provider } from '@angular/core';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { Cobro, Cupo, EstadoBarrera, EventoCarril, Usuario } from '../models/parking.models';

export interface AuthCredentials { readonly username: string; readonly password: string; }
export interface AuthService {
  currentUser(): Observable<Usuario | null>;
  signIn(credentials: AuthCredentials): Observable<Usuario | null>;
}
export interface LaneService {
  latestEvent(lane: 'entrada' | 'salida'): Observable<EventoCarril>;
  barrierState(lane: 'entrada' | 'salida'): Observable<EstadoBarrera>;
}
export interface ParkingCapacityService { getCapacity(): Observable<Cupo[]>; }
export interface PaymentService { pendingCharge(plate: string): Observable<Cobro | null>; }

export const AUTH_SERVICE = new InjectionToken<AuthService>('AUTH_SERVICE');
export const LANE_SERVICE = new InjectionToken<LaneService>('LANE_SERVICE');
export const PARKING_CAPACITY_SERVICE = new InjectionToken<ParkingCapacityService>('PARKING_CAPACITY_SERVICE');
export const PAYMENT_SERVICE = new InjectionToken<PaymentService>('PAYMENT_SERVICE');

export class MockAuthService implements AuthService {
  private readonly user = new BehaviorSubject<Usuario | null>(null);

  currentUser(): Observable<Usuario | null> { return this.user.asObservable(); }
  signIn(credentials: AuthCredentials): Observable<Usuario> {
    const user: Usuario = { id: 'demo-1', nombre: credentials.username, rol: 'operador' };
    this.user.next(user);
    return of(user);
  }
}
export class HttpAuthService implements AuthService {
  currentUser(): Observable<Usuario | null> { // TODO: consultar la sesión en Laravel.
    return of(null);
  }
  signIn(_credentials: AuthCredentials): Observable<Usuario | null> { // TODO: enviar credenciales a Laravel.
    return of(null);
  }
}
export class MockLaneService implements LaneService {
  latestEvent(lane: 'entrada' | 'salida'): Observable<EventoCarril> {
    return of({
      id: 'demo-event-1',
      tipo: lane,
      fecha: '2026-11-20T08:30:00-05:00',
      placa: 'ABC123',
      tipoVehiculo: 'carro',
      confianza: 0.98,
      lecturaManual: false,
      sedeNombre: 'Sede principal',
      carrilNombre: lane === 'entrada' ? 'Carril de entrada 1' : 'Carril de salida 1',
    });
  }
  barrierState(lane: 'entrada' | 'salida'): Observable<EstadoBarrera> {
    return of(lane === 'entrada'
      ? { abierta: true }
      : { abierta: false, motivo: 'Cobro pendiente' });
  }
}
export class HttpLaneService implements LaneService {
  latestEvent(lane: 'entrada' | 'salida'): Observable<EventoCarril> { // TODO: conectar con el endpoint de carriles.
    return of({ id: '', tipo: lane, fecha: '', placa: '' });
  }
  barrierState(_lane: 'entrada' | 'salida'): Observable<EstadoBarrera> { // TODO: conectar con el estado del controlador de barrera.
    return of({ abierta: false });
  }
}
export class MockParkingCapacityService implements ParkingCapacityService {
  getCapacity(): Observable<Cupo[]> { return of([{ tipo: 'carro', disponibles: 12, total: 40 }, { tipo: 'moto', disponibles: 8, total: 24 }]); }
}
export class HttpParkingCapacityService implements ParkingCapacityService {
  getCapacity(): Observable<Cupo[]> { // TODO: conectar con el endpoint de cupos.
    return of([]);
  }
}
export class MockPaymentService implements PaymentService {
  pendingCharge(plate: string): Observable<Cobro> { return of({ id: 'demo-charge-1', placa: plate, estado: 'pendiente', tarifaAplicada: 'diurna', valor: 5000 }); }
}
export class HttpPaymentService implements PaymentService {
  pendingCharge(_plate: string): Observable<Cobro | null> { // TODO: conectar con el endpoint de cobros.
    return of(null);
  }
}

export const MOCK_BACKEND_PROVIDERS: Provider[] = [
  { provide: AUTH_SERVICE, useClass: MockAuthService },
  { provide: LANE_SERVICE, useClass: MockLaneService },
  { provide: PARKING_CAPACITY_SERVICE, useClass: MockParkingCapacityService },
  { provide: PAYMENT_SERVICE, useClass: MockPaymentService },
];
// Para conectar Laravel, sustituir cada useClass por su adaptador Http correspondiente.
export const HTTP_BACKEND_PROVIDERS: Provider[] = [
  { provide: AUTH_SERVICE, useClass: HttpAuthService },
  { provide: LANE_SERVICE, useClass: HttpLaneService },
  { provide: PARKING_CAPACITY_SERVICE, useClass: HttpParkingCapacityService },
  { provide: PAYMENT_SERVICE, useClass: HttpPaymentService },
];

