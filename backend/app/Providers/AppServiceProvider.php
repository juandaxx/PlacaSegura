<?php

namespace App\Providers;

use App\Domain\Contracts\CobroRepository;
use App\Domain\Contracts\ConfiguracionOperativa;
use App\Domain\Contracts\ConsultaCobros;
use App\Domain\Contracts\ConsultaTarifas;
use App\Domain\Contracts\CupoRepository;
use App\Domain\Contracts\EventoAccesoRepository;
use App\Domain\Contracts\FranjaRepository;
use App\Domain\Contracts\GestorTransacciones;
use App\Domain\Contracts\PagoRepository;
use App\Domain\Contracts\Reloj;
use App\Domain\Contracts\TarifaRepository;
use App\Domain\Contracts\TipoVehiculoRepository;
use App\Infrastructure\Persistencia\DbGestorTransacciones;
use App\Infrastructure\Reloj\RelojSistema;
use App\Infrastructure\Repositories\EloquentCobroRepository;
use App\Infrastructure\Repositories\EloquentConfiguracionOperativa;
use App\Infrastructure\Repositories\EloquentCupoRepository;
use App\Infrastructure\Repositories\EloquentEventoAccesoRepository;
use App\Infrastructure\Repositories\EloquentFranjaRepository;
use App\Infrastructure\Repositories\EloquentPagoRepository;
use App\Infrastructure\Repositories\EloquentTarifaRepository;
use App\Infrastructure\Repositories\EloquentTipoVehiculoRepository;
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
        $this->app->bind(CobroRepository::class, EloquentCobroRepository::class);
        $this->app->bind(ConsultaCobros::class, EloquentCobroRepository::class);
        $this->app->bind(EventoAccesoRepository::class, EloquentEventoAccesoRepository::class);
        $this->app->bind(CupoRepository::class, EloquentCupoRepository::class);
        $this->app->bind(TipoVehiculoRepository::class, EloquentTipoVehiculoRepository::class);
        $this->app->bind(FranjaRepository::class, EloquentFranjaRepository::class);
        $this->app->bind(ConfiguracionOperativa::class, EloquentConfiguracionOperativa::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
