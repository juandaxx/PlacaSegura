import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { AUTH_SERVICE, MockAuthService } from '../../../core/api/parking.services';
import { LoginComponent } from './login.component';

describe('LoginComponent', () => {
  it('renders labeled credentials fields and a submit button', async () => {
    await TestBed.configureTestingModule({
      imports: [LoginComponent],
      providers: [provideRouter([]), { provide: AUTH_SERVICE, useClass: MockAuthService }],
    }).compileComponents();

    const fixture = TestBed.createComponent(LoginComponent);
    fixture.detectChanges();
    const element = fixture.nativeElement as HTMLElement;
    expect(element.querySelector('label[for="username"]')).toBeTruthy();
    expect(element.querySelector('label[for="password"]')).toBeTruthy();
    expect(element.querySelector('button[type="submit"]')?.textContent).toContain('Ingresar');
  });

  it('shows validation messages for empty credentials', async () => {
    await TestBed.configureTestingModule({
      imports: [LoginComponent],
      providers: [provideRouter([]), { provide: AUTH_SERVICE, useClass: MockAuthService }],
    }).compileComponents();

    const fixture = TestBed.createComponent(LoginComponent);
    fixture.detectChanges();
    (fixture.nativeElement as HTMLElement).querySelector('form')?.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    fixture.detectChanges();
    expect((fixture.nativeElement as HTMLElement).textContent).toContain('Ingresa tu usuario.');
    expect((fixture.nativeElement as HTMLElement).textContent).toContain('La contraseña debe tener al menos 6 caracteres.');
  });
});
