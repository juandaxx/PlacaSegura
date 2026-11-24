// Contratos provisionales: confirmar nombres y valores con el backend Laravel.
export type VehicleType = 'carro' | 'moto';
export type UserRole = 'operador' | 'administrador';
export type ChargeStatus = 'pendiente' | 'pagado';
export type AppliedRate = 'diurna' | 'nocturna';

export interface Vehiculo { readonly placa: string; readonly tipo: VehicleType; }
export interface LecturaPlaca { readonly placa: string; readonly confianza: number; readonly lecturaManual: boolean; }
export interface EventoCarril {
  readonly id: string;
  readonly tipo: 'entrada' | 'salida';
  readonly fecha: string;
  readonly placa: string;
  readonly tipoVehiculo?: VehicleType;
  readonly confianza?: number;
  readonly lecturaManual?: boolean;
  readonly sedeNombre?: string;
  readonly carrilNombre?: string;
}
export interface EstadoBarrera { readonly abierta: boolean; readonly motivo?: string; }
export interface Cobro { readonly id: string; readonly placa: string; readonly estado: ChargeStatus; readonly tarifaAplicada: AppliedRate; readonly valor: number; }
export interface Cupo { readonly tipo: VehicleType; readonly disponibles: number; readonly total: number; }
export interface Usuario { readonly id: string; readonly nombre: string; readonly rol: UserRole; }
