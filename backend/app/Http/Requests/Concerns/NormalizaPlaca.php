<?php

namespace App\Http\Requests\Concerns;

use App\Domain\Vehiculo\Placa;

/**
 * Normaliza el campo "placa" antes de validar: mayusculas, sin espacios ni guiones.
 */
trait NormalizaPlaca
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('placa')) {
            $this->merge(['placa' => Placa::normalizar((string) $this->input('placa'))]);
        }
    }
}
