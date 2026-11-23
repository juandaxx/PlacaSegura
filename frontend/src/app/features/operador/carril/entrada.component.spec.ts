import { TestBed } from '@angular/core/testing';
import { LANE_SERVICE, MockLaneService } from '../../../core/api/parking.services';
import { EntradaComponent } from './entrada.component';

describe('EntradaComponent', () => {
  it('renders the simulated plate, vehicle details, and open barrier state', async () => {
    await TestBed.configureTestingModule({
      imports: [EntradaComponent],
      providers: [{ provide: LANE_SERVICE, useClass: MockLaneService }],
    }).compileComponents();

    const fixture = TestBed.createComponent(EntradaComponent);
    fixture.detectChanges();
    await fixture.whenStable();
    fixture.detectChanges();

    const content = (fixture.nativeElement as HTMLElement).textContent ?? '';
    expect(content).toContain('Carril de entrada');
    expect(content).toContain('ABC123');
    expect(content).toContain('Automóvil');
    expect(content).toContain('Barrera abierta');
  });
});
