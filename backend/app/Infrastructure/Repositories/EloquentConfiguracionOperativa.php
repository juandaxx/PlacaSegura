<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\ConfiguracionOperativa;
use App\Models\Configuracion as ConfiguracionModelo;

/**
 * Valores de la tabla configuracion, con un valor por defecto si no estan.
 */
final class EloquentConfiguracionOperativa implements ConfiguracionOperativa
{
    public const UMBRAL_CONFIANZA = 'umbral_confianza';

    private const UMBRAL_CONFIANZA_POR_DEFECTO = 0.8;

    public function umbralConfianza(): float
    {
        $valor = ConfiguracionModelo::whereKey(self::UMBRAL_CONFIANZA)->value('valor');

        return is_numeric($valor) ? (float) $valor : self::UMBRAL_CONFIANZA_POR_DEFECTO;
    }
}
