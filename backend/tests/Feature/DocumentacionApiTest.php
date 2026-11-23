<?php

namespace Tests\Feature;

use Illuminate\Routing\Route as RutaLaravel;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * Mantiene la documentacion de la API (public/openapi.yaml) al dia con el codigo.
 */
class DocumentacionApiTest extends TestCase
{
    private const METODOS_HTTP = ['get', 'post', 'put', 'patch', 'delete'];

    public function test_la_pagina_de_documentacion_muestra_swagger_ui(): void
    {
        $this->get('/docs')
            ->assertOk()
            ->assertSee('swagger-ui', false)
            ->assertSee('openapi.yaml', false);
    }

    public function test_la_especificacion_documenta_exactamente_las_rutas_de_la_api(): void
    {
        $documentadas = [];

        foreach ($this->especificacion()['paths'] as $ruta => $operaciones) {
            foreach (array_keys($operaciones) as $metodo) {
                if (in_array($metodo, self::METODOS_HTTP, true)) {
                    $documentadas[] = strtoupper($metodo).' '.$this->normalizar('/api'.$ruta);
                }
            }
        }

        $registradas = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RutaLaravel $ruta) => str_starts_with($ruta->uri(), 'api/'))
            ->flatMap(fn (RutaLaravel $ruta) => collect($ruta->methods())
                ->reject(fn (string $metodo) => $metodo === 'HEAD')
                ->map(fn (string $metodo) => $metodo.' '.$this->normalizar('/'.$ruta->uri())))
            ->values()
            ->all();

        sort($documentadas);
        sort($registradas);

        $this->assertSame(
            $registradas,
            $documentadas,
            'public/openapi.yaml debe documentar exactamente las rutas de routes/api.php.',
        );
    }

    public function test_todas_las_referencias_de_la_especificacion_existen(): void
    {
        $especificacion = $this->especificacion();

        foreach ($this->referencias($especificacion) as $referencia) {
            $nodo = $especificacion;

            foreach (explode('/', substr($referencia, 2)) as $parte) {
                $this->assertArrayHasKey($parte, $nodo, "La referencia {$referencia} no existe.");
                $nodo = $nodo[$parte];
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function especificacion(): array
    {
        $especificacion = Yaml::parseFile(public_path('openapi.yaml'));

        $this->assertStringStartsWith('3.', $especificacion['openapi']);

        return $especificacion;
    }

    /**
     * @param  array<mixed>  $nodo
     * @return list<string>
     */
    private function referencias(array $nodo): array
    {
        $referencias = [];

        foreach ($nodo as $clave => $valor) {
            if ($clave === '$ref' && is_string($valor)) {
                $referencias[] = $valor;
            } elseif (is_array($valor)) {
                $referencias = [...$referencias, ...$this->referencias($valor)];
            }
        }

        return $referencias;
    }

    /**
     * Los nombres de los parametros pueden variar: /cobros/{cobro} equivale a /cobros/{id}.
     */
    private function normalizar(string $ruta): string
    {
        return preg_replace('/\{[^}]+\}/', '{}', $ruta);
    }
}
