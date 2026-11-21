<?php

namespace App\Http\Requests;

use App\Domain\Comun\Pagina;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Paginacion comun a todos los listados: parametros page y per_page.
 *
 * Los listados con filtros pueden extender esta clase y agregar sus reglas.
 */
class ListadoRequest extends FormRequest
{
    public const POR_PAGINA_DEFECTO = 15;

    public const POR_PAGINA_MAXIMO = 100;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::POR_PAGINA_MAXIMO],
        ];
    }

    public function pagina(): int
    {
        return $this->integer('page', 1);
    }

    public function porPagina(): int
    {
        return $this->integer('per_page', self::POR_PAGINA_DEFECTO);
    }

    /**
     * Convierte una pagina del dominio en el paginador de Laravel, que genera
     * la respuesta estandar con data, links y meta.
     *
     * @template T
     *
     * @param  Pagina<T>  $pagina
     * @return LengthAwarePaginator<int, T>
     */
    public function paginador(Pagina $pagina): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $pagina->elementos,
            $pagina->total,
            $pagina->porPagina,
            $pagina->numero,
            ['path' => $this->url(), 'query' => $this->query()],
        );
    }
}
