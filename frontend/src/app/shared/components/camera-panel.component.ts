import { AfterViewInit, Component, ElementRef, InjectionToken, OnDestroy, ViewChild, inject, input, signal } from '@angular/core';

export interface DetectionBox { readonly x: number; readonly y: number; readonly width: number; readonly height: number; }
export interface CameraSource { start(video: HTMLVideoElement): Promise<void>; stop(): void; }
export const CAMERA_SOURCE = new InjectionToken<CameraSource>('CAMERA_SOURCE');

export class WebcamSource implements CameraSource {
  private stream?: MediaStream;

  async start(video: HTMLVideoElement): Promise<void> {
    if (!navigator.mediaDevices?.getUserMedia) throw new Error('unavailable');
    this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
    video.srcObject = this.stream;
    await video.play();
  }

  stop(): void {
    this.stream?.getTracks().forEach(track => track.stop());
    this.stream = undefined;
  }
}

export type CameraState = 'loading' | 'live' | 'denied' | 'unavailable';

@Component({
  selector: 'app-camera-panel',
  providers: [{ provide: CAMERA_SOURCE, useClass: WebcamSource }],
  template: `
    <section class="overflow-hidden rounded-card bg-slate-950 shadow-card" aria-label="Vista de cámara">
      <div class="relative aspect-video min-h-52">
        <video #video class="absolute inset-0 size-full object-cover" autoplay muted playsinline [class.hidden]="state() !== 'live'" aria-label="Video en vivo de la cámara"></video>
        @if (state() !== 'live') {
          <div class="absolute inset-0 grid place-items-center bg-slate-950 px-5 text-center text-white">
            <div><div class="mx-auto mb-3 grid size-14 place-items-center rounded-full bg-white/10 text-2xl" aria-hidden="true">◉</div>
              <p class="font-heading text-base font-semibold">{{ state() === 'loading' ? 'Conectando cámara…' : 'Sin señal' }}</p>
              @if (state() !== 'loading') { <p class="mt-1 text-sm text-slate-300">{{ state() === 'denied' ? 'Permite el acceso a la cámara para continuar.' : 'No se encontró una cámara disponible.' }}</p>
                <button type="button" class="mt-4 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-ink hover:bg-slate-100" (click)="connect(video)">Reintentar</button> }
            </div>
          </div>
        }
        @if (bbox(); as box) { <div class="pointer-events-none absolute border-2 border-success" [style.left.%]="box.x" [style.top.%]="box.y" [style.width.%]="box.width" [style.height.%]="box.height" aria-hidden="true"></div> }
        <div class="absolute left-4 top-4 inline-flex items-center gap-2 rounded-full bg-slate-950/70 px-3 py-1.5 text-xs font-medium text-white backdrop-blur">
          <span class="size-2 rounded-full" [class.bg-success]="state() === 'live'" [class.bg-slate-400]="state() !== 'live'"></span>{{ state() === 'live' ? 'Cámara en vivo' : 'Sin señal' }}
        </div>
        <ng-content />
      </div>
      <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-white/10 bg-slate-900 px-4 py-3 text-xs text-slate-300 sm:px-5">
        <span>{{ lane() }} · {{ site() }}</span><time>{{ timestamp() }}</time>
      </footer>
    </section>
  `,
})
export class CameraPanelComponent implements AfterViewInit, OnDestroy {
  @ViewChild('video') private videoElement?: ElementRef<HTMLVideoElement>;
  readonly lane = input('Carril de entrada');
  readonly site = input('Sede principal');
  readonly timestamp = input('');
  readonly bbox = input<DetectionBox | null>(null);
  protected readonly state = signal<CameraState>('loading');
  private readonly source = inject(CAMERA_SOURCE);
  ngAfterViewInit(): void {
    if (this.videoElement) void this.connect(this.videoElement.nativeElement);
  }

  async connect(video: HTMLVideoElement): Promise<void> {
    this.source.stop();
    this.state.set('loading');
    try {
      await this.source.start(video);
      this.state.set('live');
    } catch (error) {
      this.state.set(error instanceof DOMException && error.name === 'NotAllowedError' ? 'denied' : 'unavailable');
    }
  }

  ngOnDestroy(): void { this.source.stop(); }
}
