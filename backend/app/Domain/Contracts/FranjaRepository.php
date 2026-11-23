<?php

namespace App\Domain\Contracts;

use App\Domain\Tarifa\Franja;

/**
 * Franjas horarias configuradas (diurna y nocturna).
 */
interface FranjaRepository
{
    /**
     * @return list<Franja>
     */
    public function todas(): array;
}
