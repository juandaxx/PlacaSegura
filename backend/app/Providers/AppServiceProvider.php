<?php

namespace App\Providers;

use App\Domain\Contracts\ConsultaPagos;
use App\Domain\Contracts\ConsultasDashboard;
use App\Domain\Contracts\CobroRepository;
use App\Domain\Contracts\ConsultaCobros;
use App\Domain\Contracts\ConsultaTarifas;
use App\Domain\Contracts\GestorTransacciones;
use App\Domain\Contracts\PagoRepository;
use App\Domain\Contracts\Reloj;
use App\Domain\Contracts\TarifaRepository;
use App\Domain\Tiempo\DiaOperativo;
use App\Infrastructure\Persistencia\DbGestorTransacciones;
use App\Infrastructure\Reloj\RelojSistema;
use App\Infrastructure\Repositories\DbConsultasDashboard;
use App\Infrastructure\Repositories\DbPagoRepository;
use App\Infrastructure\Repositories\EloquentCobroRepository;
use App\Infrastructure\Repositories\EloquentPagoRepository;
use App\Infrastructure\Repositories\EloquentTarifaRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Implementacion concreta de cada contrato del dominio.
        $this->app->bind(Reloj::class, RelojSistema::class);
        $this->app->bind(GestorTransacciones::class, DbGestorTransacciones::class);
        $this->app->bind(PagoRepository::class, EloquentPagoRepository::class);
        $this->app->bind(TarifaRepository::class, EloquentTarifaRepository::class);
        $this->app->bind(ConsultaTarifas::class, EloquentTarifaRepository::class);
        $this->app->bind(ConsultasDashboard::class, DbConsultasDashboard::class);
        $this->app->singleton(DiaOperativo::class, fn () => new DiaOperativo(
            (string) config('placasegura.hora_inicio_dia_operativo', '06:00'),
        ));
        $this->app->bind(CobroRepository::class, EloquentCobroRepository::class);
        $this->app->bind(ConsultaCobros::class, EloquentCobroRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
