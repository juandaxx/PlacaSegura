<?php

namespace App\Providers;

use App\Domain\Contracts\ConsultaPagos;
use App\Domain\Contracts\ConsultaTarifas;
use App\Domain\Contracts\GestorTransacciones;
use App\Domain\Contracts\PagoRepository;
use App\Domain\Contracts\Reloj;
use App\Domain\Contracts\TarifaRepository;
use App\Infrastructure\Persistencia\DbGestorTransacciones;
use App\Infrastructure\Reloj\RelojSistema;
use App\Infrastructure\Repositories\DbPagoRepository;
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
        $this->app->bind(PagoRepository::class, DbPagoRepository::class);
        $this->app->bind(ConsultaPagos::class, DbPagoRepository::class);
        $this->app->bind(TarifaRepository::class, EloquentTarifaRepository::class);
        $this->app->bind(ConsultaTarifas::class, EloquentTarifaRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
