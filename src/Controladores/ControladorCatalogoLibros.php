<?php

declare(strict_types=1);

namespace Fabularia\Controladores;

use Fabularia\Servicios\ServicioCatalogoLibros;
use Fabularia\Servicios\ServicioLecturaPublica;
use RuntimeException;

final class ControladorCatalogoLibros
{
    public function __construct(
        private readonly ServicioCatalogoLibros $servicioCatalogoLibros,
        private readonly ServicioLecturaPublica $servicioLecturaPublica
    )
    {
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function sugerencias(): array
    {
        $texto = trim((string) ($_GET['texto'] ?? ''));
        if (mb_strlen($texto) < 2) {
            return [200, ['sugerencias' => []]];
        }

        $sugerencias = $this->servicioCatalogoLibros->buscarSugerencias($texto, 8);
        return [200, ['sugerencias' => $sugerencias]];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function catalogoLibre(): array
    {
        $texto = trim((string) ($_GET['texto'] ?? ''));
        $paginaEs = max(1, (int) ($_GET['pagina_es'] ?? 1));
        $paginaEn = max(1, (int) ($_GET['pagina_en'] ?? 1));
        $porPagina = 10;

        $seccionEs = $this->servicioLecturaPublica->buscarLibrosCatalogoLibrePaginadoPorIdioma(
            $texto,
            'es',
            $paginaEs,
            $porPagina
        );
        $seccionEn = $this->servicioLecturaPublica->buscarLibrosCatalogoLibrePaginadoPorIdioma(
            $texto,
            'en',
            $paginaEn,
            $porPagina
        );

        return [200, [
            'texto' => $texto,
            'libros_es' => $seccionEs['libros'] ?? [],
            'libros_en' => $seccionEn['libros'] ?? [],
            'paginacion' => [
                'es' => [
                    'pagina_actual' => (int) ($seccionEs['pagina_actual'] ?? $paginaEs),
                    'por_pagina' => (int) ($seccionEs['por_pagina'] ?? $porPagina),
                    'hay_siguiente' => (bool) ($seccionEs['hay_siguiente'] ?? false),
                ],
                'en' => [
                    'pagina_actual' => (int) ($seccionEn['pagina_actual'] ?? $paginaEn),
                    'por_pagina' => (int) ($seccionEn['por_pagina'] ?? $porPagina),
                    'hay_siguiente' => (bool) ($seccionEn['hay_siguiente'] ?? false),
                ],
            ],
        ]];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function leerCatalogoLibre(): array
    {
        $idExterno = trim((string) ($_GET['id_externo'] ?? ''));
        $pagina = (int) ($_GET['pagina'] ?? 1);

        if ($idExterno === '') {
            return [422, ['error' => 'Debes indicar id_externo para leer el libro gratuito.']];
        }

        try {
            $lectura = $this->servicioLecturaPublica->obtenerPaginaLecturaLibre($idExterno, max(1, $pagina));
            return [200, ['lectura' => $lectura]];
        } catch (RuntimeException $excepcion) {
            return [409, ['error' => $excepcion->getMessage()]];
        } catch (\Throwable) {
            return [500, ['error' => 'No se pudo cargar la lectura del catalogo gratuito.']];
        }
    }
}
