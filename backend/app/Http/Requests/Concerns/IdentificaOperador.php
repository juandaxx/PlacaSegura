<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * El operador que hace la accion es el usuario autenticado. Mientras no
 * exista el login (F-10) se acepta usuario_id en la peticion.
 */
trait IdentificaOperador
{
    /**
     * @return list<mixed>
     */
    protected function reglasUsuarioId(): array
    {
        return [
            Rule::requiredIf(fn () => $this->user() === null),
            'nullable',
            'integer',
            Rule::exists('usuarios', 'id')->where('activo', true),
        ];
    }

    public function usuarioId(): int
    {
        return (int) ($this->user()?->getAuthIdentifier() ?? $this->integer('usuario_id'));
    }
}
