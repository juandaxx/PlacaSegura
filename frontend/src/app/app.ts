import { HttpClient } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { JsonPipe } from '@angular/common';

interface ConnectionStatus {
  connected: boolean;
  message: string;
  backend: string;
  timestamp: string;
}

const API_URL = 'http://127.0.0.1:8000/api';

@Component({
  imports: [JsonPipe],
  selector: 'app-root',
  styleUrl: './app.css',
  templateUrl: './app.html',
})
export class App {
  private readonly http = inject(HttpClient);
  protected readonly status = signal<'loading' | 'connected' | 'disconnected'>('loading');
  protected readonly response = signal<ConnectionStatus | null>(null);
  protected readonly checkedAt = signal('');

  constructor() {
    this.checkConnection();
  }

  protected checkConnection(): void {
    this.status.set('loading');
    this.http.get<ConnectionStatus>(`${API_URL}/connection-status`).subscribe({
      next: (response) => {
        this.response.set(response);
        this.status.set(response.connected ? 'connected' : 'disconnected');
        this.checkedAt.set(new Date().toLocaleTimeString());
      },
      error: () => {
        this.response.set(null);
        this.status.set('disconnected');
        this.checkedAt.set(new Date().toLocaleTimeString());
      },
    });
  }
}
