import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { DashboardLayoutComponent } from './layout/dashboard-layout.component';

describe('DashboardLayoutComponent', () => {
  it('creates the shared dashboard shell', async () => {
    await TestBed.configureTestingModule({ imports: [DashboardLayoutComponent], providers: [provideRouter([])] }).compileComponents();
    const fixture = TestBed.createComponent(DashboardLayoutComponent);
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('app-sidebar')).toBeTruthy();
    expect(fixture.nativeElement.querySelector('app-topbar')).toBeTruthy();
  });
});
