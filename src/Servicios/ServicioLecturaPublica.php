<?php

declare(strict_types=1);

namespace Fabularia\Servicios;

use Monolog\Logger;
use RuntimeException;

final class ServicioLecturaPublica
{
    private const MAX_BYTES_DESCARGA = 3_500_000;
    private const MAX_CHARS_PAGINA = 2100;

    public function __construct(
        private readonly Logger $logger,
        private readonly string $directorioCacheLecturas
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buscarReferenciaPublica(string $titulo, string $autor): array
    {
        $titulo = trim($titulo);
        $autor = trim($autor);
        if ($titulo === '') {
            return $this->referenciaVacia();
        }

        try {
            $query = trim($titulo . ' ' . $autor);
            $url = 'https://gutendex.com/books?search=' . rawurlencode($query) . '&languages=es';
            $datos = $this->obtenerJsonConReintento($url, 2);
            $resultados = $datos['results'] ?? [];

            if (!is_array($resultados) || count($resultados) === 0) {
                return $this->referenciaVacia();
            }

            $mejor = $this->seleccionarMejorResultado($resultados, $titulo, $autor);
            if ($mejor === null) {
                return $this->referenciaVacia();
            }

            return $mejor;
        } catch (\Throwable $excepcion) {
            $this->logger->warning('No se pudo enriquecer referencia de lectura publica', [
                'titulo' => $titulo,
                'autor' => $autor,
                'mensaje' => $excepcion->getMessage(),
            ]);
            return $this->referenciaVacia();
        }
    }

    /**
     * @param array<string, mixed> $prestamo
     * @return array<string, mixed>
     */
    public function obtenerPaginaLectura(array $prestamo, int $paginaSolicitada): array
    {
        $lecturaPublica = (int) ($prestamo['lectura_publica'] ?? 0) === 1;
        $url = trim((string) ($prestamo['lectura_url'] ?? ''));

        if (!$lecturaPublica || $url === '') {
            throw new RuntimeException('El libro de este prestamo no tiene lectura publica disponible.');
        }

        $fuente = trim((string) ($prestamo['lectura_fuente'] ?? ''));
        $idExterno = trim((string) ($prestamo['lectura_id_externo'] ?? ''));
        $formato = trim((string) ($prestamo['lectura_formato'] ?? ''));
        $cacheKey = sha1($fuente . '|' . $idExterno . '|' . $url . '|' . $formato);
        $paginas = $this->obtenerPaginasDesdeCache($cacheKey, $url, $formato, true);

        $totalPaginas = count($paginas);
        if ($totalPaginas <= 0) {
            throw new RuntimeException('No se pudo generar contenido paginado para este libro.');
        }

        $paginaActual = max(1, min($paginaSolicitada, $totalPaginas));
        $contenido = (string) ($paginas[$paginaActual - 1] ?? '');
        if (trim($contenido) === '') {
            $contenido = 'Sin contenido disponible para esta pagina.';
        }

        $progreso = (float) round(($paginaActual / $totalPaginas) * 100, 2);

        return [
            'pagina_actual' => $paginaActual,
            'total_paginas' => $totalPaginas,
            'porcentaje_progreso' => $progreso,
            'contenido' => $contenido,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buscarLibrosCatalogoLibre(string $textoBusqueda, int $maxResultados = 18): array
    {
        $maxResultados = max(1, min(30, $maxResultados));
        $textoBusqueda = trim($textoBusqueda);

        try {
            $salida = [];
            $idsVistos = [];

            // Primera pasada: prioriza libros en espanol para que la seccion no quede vacia.
            $limiteEspanol = max($maxResultados, (int) ceil($maxResultados * 1.4));
            $librosEspanol = $this->consultarCatalogoLibrePorIdioma($textoBusqueda, 'es', $limiteEspanol);
            $this->anexarLibrosCatalogoLibre($salida, $idsVistos, $librosEspanol, $maxResultados);

            // Si la API trae poco en espanol, rellena con fallback en espanol para no dejarlo vacio.
            $minimoEspanol = min($maxResultados, max(6, (int) ceil($maxResultados * 0.4)));
            if (count($salida) < $minimoEspanol) {
                $faltan = $minimoEspanol - count($salida);
                $fallbackEspanol = $this->filtrarCatalogoLibreFallbackPorIdioma($textoBusqueda, 'es', $faltan * 3);
                $this->anexarLibrosCatalogoLibre($salida, $idsVistos, $fallbackEspanol, $maxResultados);
            }

            // Segunda pasada: completa con ingles solo si faltan resultados.
            if (count($salida) < $maxResultados) {
                $restantes = $maxResultados - count($salida);
                $limiteIngles = max($restantes, (int) ceil($restantes * 1.5));
                $librosIngles = $this->consultarCatalogoLibrePorIdioma($textoBusqueda, 'en', $limiteIngles);
                $this->anexarLibrosCatalogoLibre($salida, $idsVistos, $librosIngles, $maxResultados);
            }

            if (count($salida) > 0) {
                return $salida;
            }

            return $this->filtrarCatalogoLibreFallback($textoBusqueda, $maxResultados);
        } catch (\Throwable $excepcion) {
            $this->logger->warning('No se pudo consultar catalogo libre de lectura', [
                'texto' => $textoBusqueda,
                'mensaje' => $excepcion->getMessage(),
            ]);

            return $this->filtrarCatalogoLibreFallback($textoBusqueda, $maxResultados);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function buscarLibrosCatalogoLibrePaginadoPorIdioma(
        string $textoBusqueda,
        string $idioma,
        int $pagina,
        int $porPagina = 10
    ): array {
        $idioma = trim(mb_strtolower($idioma, 'UTF-8'));
        if (!in_array($idioma, ['es', 'en'], true)) {
            return [
                'idioma' => $idioma,
                'libros' => [],
                'pagina_actual' => 1,
                'por_pagina' => max(1, min(20, $porPagina)),
                'hay_siguiente' => false,
            ];
        }

        $pagina = max(1, $pagina);
        $porPagina = max(1, min(20, $porPagina));
        $textoBusqueda = trim($textoBusqueda);

        try {
            $baseUrl = 'https://gutendex.com/books?languages=' . rawurlencode($idioma);
            if ($textoBusqueda !== '') {
                $baseUrl .= '&search=' . rawurlencode($textoBusqueda);
            }

            $offset = ($pagina - 1) * $porPagina;
            $necesarios = $offset + $porPagina + 1; // +1 para saber si hay siguiente pagina
            $maxPaginasRemotas = max(5, min(30, $pagina + 12));

            $librosFiltrados = [];
            $idsVistos = [];
            $hayMasRemoto = true;
            $paginaRemota = 1;

            while ($hayMasRemoto && $paginaRemota <= $maxPaginasRemotas && count($librosFiltrados) < $necesarios) {
                $url = $baseUrl . '&page=' . rawurlencode((string) $paginaRemota);
                $datos = $this->obtenerJsonConReintento($url, 2);
                $resultados = $datos['results'] ?? [];
                $hayMasRemoto = trim((string) ($datos['next'] ?? '')) !== '';

                if (!is_array($resultados) || count($resultados) === 0) {
                    $paginaRemota++;
                    continue;
                }

                foreach ($resultados as $resultado) {
                    if (!is_array($resultado)) {
                        continue;
                    }

                    $libro = $this->normalizarLibroCatalogoLibre($resultado);
                    if ($libro === null) {
                        continue;
                    }

                    if (trim((string) ($libro['idioma'] ?? '')) !== $idioma) {
                        continue;
                    }

                    $idExterno = trim((string) ($libro['id_externo'] ?? ''));
                    if ($idExterno === '' || isset($idsVistos[$idExterno])) {
                        continue;
                    }

                    $idsVistos[$idExterno] = true;
                    $this->guardarReferenciaCatalogoLibreEnCache($libro);
                    $librosFiltrados[] = $libro;

                    if (count($librosFiltrados) >= $necesarios) {
                        break;
                    }
                }

                $paginaRemota++;
            }

            $librosPagina = array_slice($librosFiltrados, $offset, $porPagina);
            $haySiguiente = count($librosFiltrados) > ($offset + $porPagina);

            // Si no hemos agotado Gutendex todavia, puede haber mas resultados.
            if (!$haySiguiente && $hayMasRemoto) {
                $haySiguiente = true;
            }

            if (count($librosPagina) < $porPagina) {
                $fallback = $this->obtenerFallbackPaginadoPorIdioma($textoBusqueda, $idioma, $pagina, $porPagina);
                foreach ($fallback['libros'] as $libroFallback) {
                    $idExterno = trim((string) ($libroFallback['id_externo'] ?? ''));
                    if ($idExterno === '' || isset($idsVistos[$idExterno])) {
                        continue;
                    }
                    $idsVistos[$idExterno] = true;
                    $this->guardarReferenciaCatalogoLibreEnCache($libroFallback);
                    $librosPagina[] = $libroFallback;
                    if (count($librosPagina) >= $porPagina) {
                        break;
                    }
                }
                $haySiguiente = $haySiguiente || (bool) ($fallback['hay_siguiente'] ?? false);
            }

            return [
                'idioma' => $idioma,
                'libros' => array_slice($librosPagina, 0, $porPagina),
                'pagina_actual' => $pagina,
                'por_pagina' => $porPagina,
                'hay_siguiente' => $haySiguiente,
            ];
        } catch (\Throwable $excepcion) {
            $this->logger->warning('Fallo al paginar catalogo libre por idioma', [
                'idioma' => $idioma,
                'pagina' => $pagina,
                'texto' => $textoBusqueda,
                'mensaje' => $excepcion->getMessage(),
            ]);

            $fallback = $this->obtenerFallbackPaginadoPorIdioma($textoBusqueda, $idioma, $pagina, $porPagina);
            foreach ($fallback['libros'] as $libroFallback) {
                $this->guardarReferenciaCatalogoLibreEnCache($libroFallback);
            }

            return [
                'idioma' => $idioma,
                'libros' => $fallback['libros'],
                'pagina_actual' => $pagina,
                'por_pagina' => $porPagina,
                'hay_siguiente' => (bool) ($fallback['hay_siguiente'] ?? false),
            ];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function consultarCatalogoLibrePorIdioma(string $textoBusqueda, string $idioma, int $maxResultados): array
    {
        $idioma = trim(mb_strtolower($idioma, 'UTF-8'));
        if (!in_array($idioma, ['es', 'en'], true)) {
            return [];
        }

        $maxResultados = max(1, min(60, $maxResultados));
        $url = 'https://gutendex.com/books?languages=' . rawurlencode($idioma);
        if (trim($textoBusqueda) !== '') {
            $url .= '&search=' . rawurlencode($textoBusqueda);
        }

        $datos = $this->obtenerJsonConReintento($url, 2);
        $resultados = $datos['results'] ?? [];
        if (!is_array($resultados) || count($resultados) === 0) {
            return [];
        }

        $salida = [];
        $idsVistos = [];
        foreach ($resultados as $resultado) {
            if (!is_array($resultado)) {
                continue;
            }

            $libro = $this->normalizarLibroCatalogoLibre($resultado);
            if ($libro === null) {
                continue;
            }

            if (trim((string) ($libro['idioma'] ?? '')) !== $idioma) {
                continue;
            }

            $idExterno = trim((string) ($libro['id_externo'] ?? ''));
            if ($idExterno === '' || isset($idsVistos[$idExterno])) {
                continue;
            }

            $idsVistos[$idExterno] = true;
            $salida[] = $libro;

            if (count($salida) >= $maxResultados) {
                break;
            }
        }

        return $salida;
    }

    /**
     * @param array<int, array<string, mixed>> $destino
     * @param array<string, bool> $idsVistos
     * @param array<int, array<string, mixed>> $nuevos
     */
    private function anexarLibrosCatalogoLibre(
        array &$destino,
        array &$idsVistos,
        array $nuevos,
        int $maxResultados
    ): void {
        foreach ($nuevos as $libro) {
            $idExterno = trim((string) ($libro['id_externo'] ?? ''));
            if ($idExterno === '' || isset($idsVistos[$idExterno])) {
                continue;
            }

            $idsVistos[$idExterno] = true;
            $this->guardarReferenciaCatalogoLibreEnCache($libro);
            $destino[] = $libro;

            if (count($destino) >= $maxResultados) {
                break;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function obtenerPaginaLecturaLibre(string $idExterno, int $paginaSolicitada): array
    {
        $idExterno = trim($idExterno);
        if ($idExterno === '') {
            throw new RuntimeException('Debes indicar un id_externo valido para lectura libre.');
        }

        $libro = $this->cargarReferenciaCatalogoLibreDesdeCache($idExterno);
        if ($libro === null && ctype_digit($idExterno)) {
            $url = 'https://gutendex.com/books?ids=' . rawurlencode($idExterno);
            $datos = $this->obtenerJsonConReintento($url, 2);
            $resultados = $datos['results'] ?? [];
            if (!is_array($resultados) || count($resultados) === 0 || !is_array($resultados[0])) {
                throw new RuntimeException('No se encontro el libro libre solicitado.');
            }

            $libro = $this->normalizarLibroCatalogoLibre($resultados[0]);
            if ($libro !== null) {
                $this->guardarReferenciaCatalogoLibreEnCache($libro);
            }
        }

        if ($libro === null) {
            throw new RuntimeException('El libro libre no tiene un formato de lectura compatible.');
        }

        $idioma = trim((string) ($libro['idioma'] ?? ''));
        $validarEspanol = $idioma !== 'en';

        $cacheKey = sha1('libre|gutendex|' . $idExterno . '|' . (string) $libro['lectura_url'] . '|' . (string) $libro['lectura_formato']);
        $paginas = $this->obtenerPaginasDesdeCache(
            $cacheKey,
            (string) $libro['lectura_url'],
            (string) $libro['lectura_formato'],
            $validarEspanol
        );

        $totalPaginas = count($paginas);
        if ($totalPaginas <= 0) {
            throw new RuntimeException('No se pudo generar contenido paginado para este libro.');
        }

        $paginaActual = max(1, min($paginaSolicitada, $totalPaginas));
        $contenido = trim((string) ($paginas[$paginaActual - 1] ?? ''));
        if ($contenido === '') {
            $contenido = 'Sin contenido disponible para esta pagina.';
        }

        return [
            'id_externo' => (string) $libro['id_externo'],
            'titulo' => (string) $libro['titulo'],
            'autor' => (string) $libro['autor'],
            'idioma' => $idioma,
            'pagina_actual' => $paginaActual,
            'total_paginas' => $totalPaginas,
            'porcentaje_progreso' => (float) round(($paginaActual / $totalPaginas) * 100, 2),
            'contenido' => $contenido,
        ];
    }

    /**
     * @param array<string, mixed> $libro
     */
    private function guardarReferenciaCatalogoLibreEnCache(array $libro): void
    {
        $idExterno = trim((string) ($libro['id_externo'] ?? ''));
        if ($idExterno === '') {
            return;
        }

        try {
            $this->asegurarDirectorioCache();
            $ruta = $this->directorioCacheLecturas . DIRECTORY_SEPARATOR . 'catalogo-libre-' . $idExterno . '.json';
            file_put_contents($ruta, json_encode($libro, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable) {
            // Cache opcional: no debe romper el flujo principal.
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function cargarReferenciaCatalogoLibreDesdeCache(string $idExterno): ?array
    {
        try {
            $this->asegurarDirectorioCache();
            $ruta = $this->directorioCacheLecturas . DIRECTORY_SEPARATOR . 'catalogo-libre-' . $idExterno . '.json';
            if (!is_file($ruta)) {
                return null;
            }

            $contenido = (string) file_get_contents($ruta);
            $datos = json_decode($contenido, true);
            return is_array($datos) ? $datos : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filtrarCatalogoLibreFallback(string $textoBusqueda, int $maxResultados): array
    {
        $texto = mb_strtolower(trim($textoBusqueda), 'UTF-8');
        $salida = [];

        foreach ($this->catalogoLibreFallback() as $libro) {
            $buscable = mb_strtolower(
                trim((string) ($libro['titulo'] ?? '') . ' ' . (string) ($libro['autor'] ?? '')),
                'UTF-8'
            );

            if ($texto !== '' && !str_contains($buscable, $texto)) {
                continue;
            }

            $this->guardarReferenciaCatalogoLibreEnCache($libro);
            $salida[] = $libro;
            if (count($salida) >= $maxResultados) {
                break;
            }
        }

        return $salida;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filtrarCatalogoLibreFallbackPorIdioma(
        string $textoBusqueda,
        string $idioma,
        int $maxResultados
    ): array {
        $texto = mb_strtolower(trim($textoBusqueda), 'UTF-8');
        $idioma = trim(mb_strtolower($idioma, 'UTF-8'));
        $salida = [];

        foreach ($this->catalogoLibreFallback() as $libro) {
            $idiomaLibro = trim(mb_strtolower((string) ($libro['idioma'] ?? ''), 'UTF-8'));
            if ($idiomaLibro !== $idioma) {
                continue;
            }

            $buscable = mb_strtolower(
                trim((string) ($libro['titulo'] ?? '') . ' ' . (string) ($libro['autor'] ?? '')),
                'UTF-8'
            );
            if ($texto !== '' && !str_contains($buscable, $texto)) {
                continue;
            }

            $salida[] = $libro;
            if (count($salida) >= $maxResultados) {
                break;
            }
        }

        return $salida;
    }

    /**
     * @return array<string, mixed>
     */
    private function obtenerFallbackPaginadoPorIdioma(
        string $textoBusqueda,
        string $idioma,
        int $pagina,
        int $porPagina
    ): array {
        $pagina = max(1, $pagina);
        $porPagina = max(1, $porPagina);

        $filtrados = $this->filtrarCatalogoLibreFallbackPorIdioma(
            $textoBusqueda,
            $idioma,
            500
        );

        $offset = ($pagina - 1) * $porPagina;
        $libros = array_slice($filtrados, $offset, $porPagina);
        $haySiguiente = count($filtrados) > ($offset + count($libros));

        return [
            'libros' => $libros,
            'hay_siguiente' => $haySiguiente,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalogoLibreFallback(): array
    {
        return [
            [
                'id_externo' => 'fallback-don-quijote',
                'titulo' => 'Don Quijote de la Mancha',
                'autor' => 'Miguel de Cervantes',
                'genero' => 'Dominio publico',
                'descripcion' => 'Clasico de la literatura espanola sobre las aventuras de Alonso Quijano y su escudero Sancho Panza.',
                'portada_url' => 'https://www.gutenberg.org/cache/epub/2000/pg2000.cover.medium.jpg',
                'idioma' => 'es',
                'idioma_etiqueta' => 'ES',
                'lectura_fuente' => 'gutenberg',
                'lectura_formato' => 'text/plain; charset=utf-8',
                'lectura_url' => 'https://www.gutenberg.org/ebooks/2000.txt.utf-8',
            ],
            [
                'id_externo' => 'fallback-crimen-castigo',
                'titulo' => 'El crimen y el castigo',
                'autor' => 'Fiodor Dostoievski',
                'genero' => 'Dominio publico',
                'descripcion' => 'Novela psicologica sobre culpa, redencion y conflicto moral en la Rusia del siglo XIX.',
                'portada_url' => 'https://www.gutenberg.org/cache/epub/61851/pg61851.cover.medium.jpg',
                'idioma' => 'es',
                'idioma_etiqueta' => 'ES',
                'lectura_fuente' => 'gutenberg',
                'lectura_formato' => 'text/plain; charset=utf-8',
                'lectura_url' => 'https://www.gutenberg.org/ebooks/61851.txt.utf-8',
            ],
            [
                'id_externo' => 'fallback-la-odisea',
                'titulo' => 'La Odisea',
                'autor' => 'Homero',
                'genero' => 'Dominio publico',
                'descripcion' => 'Poema epico del regreso de Ulises tras la guerra de Troya, con viajes y desafios miticos.',
                'portada_url' => 'https://www.gutenberg.org/cache/epub/58221/pg58221.cover.medium.jpg',
                'idioma' => 'es',
                'idioma_etiqueta' => 'ES',
                'lectura_fuente' => 'gutenberg',
                'lectura_formato' => 'text/plain; charset=utf-8',
                'lectura_url' => 'https://www.gutenberg.org/ebooks/58221.txt.utf-8',
            ],
            [
                'id_externo' => 'fallback-dona-perfecta',
                'titulo' => 'Dona Perfecta',
                'autor' => 'Benito Perez Galdos',
                'genero' => 'Dominio publico',
                'descripcion' => 'Novela espanola sobre conflicto entre tradicion, religion y modernidad en la Espana del siglo XIX.',
                'portada_url' => 'https://www.gutenberg.org/cache/epub/15725/pg15725.cover.medium.jpg',
                'idioma' => 'es',
                'idioma_etiqueta' => 'ES',
                'lectura_fuente' => 'gutenberg',
                'lectura_formato' => 'text/plain; charset=utf-8',
                'lectura_url' => 'https://www.gutenberg.org/ebooks/15725.txt.utf-8',
            ],
            [
                'id_externo' => 'fallback-divina-comedia',
                'titulo' => 'La Divina Comedia',
                'autor' => 'Dante Alighieri',
                'genero' => 'Dominio publico',
                'descripcion' => 'Version en espanol del poema epico dividido en Infierno, Purgatorio y Paraiso.',
                'portada_url' => 'https://www.gutenberg.org/cache/epub/57303/pg57303.cover.medium.jpg',
                'idioma' => 'es',
                'idioma_etiqueta' => 'ES',
                'lectura_fuente' => 'gutenberg',
                'lectura_formato' => 'text/plain; charset=utf-8',
                'lectura_url' => 'https://www.gutenberg.org/ebooks/57303.txt.utf-8',
            ],
            [
                'id_externo' => 'fallback-cuentos-amor',
                'titulo' => 'Cuentos de amor',
                'autor' => 'Horacio Quiroga',
                'genero' => 'Dominio publico',
                'descripcion' => 'Coleccion de relatos breves con tono intimista y dramatico del modernismo rioplatense.',
                'portada_url' => 'https://www.gutenberg.org/cache/epub/55514/pg55514.cover.medium.jpg',
                'idioma' => 'es',
                'idioma_etiqueta' => 'ES',
                'lectura_fuente' => 'gutenberg',
                'lectura_formato' => 'text/plain; charset=utf-8',
                'lectura_url' => 'https://www.gutenberg.org/ebooks/55514.txt.utf-8',
            ],
            [
                'id_externo' => 'fallback-pride-prejudice',
                'titulo' => 'Pride and Prejudice',
                'autor' => 'Jane Austen',
                'genero' => 'Dominio publico',
                'descripcion' => 'Classic novel about social expectations, family pressures, and emotional growth in Regency England.',
                'portada_url' => 'https://www.gutenberg.org/cache/epub/1342/pg1342.cover.medium.jpg',
                'idioma' => 'en',
                'idioma_etiqueta' => 'EN',
                'lectura_fuente' => 'gutenberg',
                'lectura_formato' => 'text/plain; charset=utf-8',
                'lectura_url' => 'https://www.gutenberg.org/cache/epub/1342/pg1342.txt',
            ],
            [
                'id_externo' => 'fallback-frankenstein',
                'titulo' => 'Frankenstein; Or, The Modern Prometheus',
                'autor' => 'Mary Shelley',
                'genero' => 'Dominio publico',
                'descripcion' => 'Foundational gothic science-fiction novel on ambition, responsibility, and identity.',
                'portada_url' => 'https://www.gutenberg.org/cache/epub/84/pg84.cover.medium.jpg',
                'idioma' => 'en',
                'idioma_etiqueta' => 'EN',
                'lectura_fuente' => 'gutenberg',
                'lectura_formato' => 'text/plain; charset=utf-8',
                'lectura_url' => 'https://www.gutenberg.org/cache/epub/84/pg84.txt',
            ],
            [
                'id_externo' => 'fallback-sherlock-holmes',
                'titulo' => 'The Adventures of Sherlock Holmes',
                'autor' => 'Arthur Conan Doyle',
                'genero' => 'Dominio publico',
                'descripcion' => 'Collection of detective stories featuring Sherlock Holmes and Dr. Watson.',
                'portada_url' => 'https://www.gutenberg.org/cache/epub/1661/pg1661.cover.medium.jpg',
                'idioma' => 'en',
                'idioma_etiqueta' => 'EN',
                'lectura_fuente' => 'gutenberg',
                'lectura_formato' => 'text/plain; charset=utf-8',
                'lectura_url' => 'https://www.gutenberg.org/cache/epub/1661/pg1661.txt',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function referenciaVacia(): array
    {
        return [
            'lectura_publica' => 0,
            'lectura_fuente' => null,
            'lectura_id_externo' => null,
            'lectura_url' => null,
            'lectura_formato' => null,
        ];
    }

    /**
     * @param array<int, mixed> $resultados
     * @return array<string, mixed>|null
     */
    private function seleccionarMejorResultado(array $resultados, string $titulo, string $autor): ?array
    {
        $tituloBase = $this->normalizarTexto($titulo);
        $autorBase = $this->normalizarTexto($autor);
        $mejor = null;
        $mejorPuntaje = -1;

        foreach ($resultados as $resultado) {
            if (!is_array($resultado)) {
                continue;
            }

            $idiomas = $resultado['languages'] ?? [];
            if (!$this->incluyeIdiomaEspanol($idiomas)) {
                continue;
            }

            $tituloResultado = trim((string) ($resultado['title'] ?? ''));
            if ($tituloResultado === '') {
                continue;
            }

            $formatos = $resultado['formats'] ?? [];
            if (!is_array($formatos)) {
                continue;
            }

            $fuente = $this->seleccionarFormatoLegible($formatos);
            if ($fuente === null) {
                continue;
            }

            $autores = $resultado['authors'] ?? [];
            $nombreAutorResultado = '';
            if (is_array($autores) && count($autores) > 0 && is_array($autores[0])) {
                $nombreAutorResultado = trim((string) ($autores[0]['name'] ?? ''));
            }

            $puntaje = $this->calcularPuntajeCoincidencia(
                $tituloBase,
                $autorBase,
                $this->normalizarTexto($tituloResultado),
                $this->normalizarTexto($nombreAutorResultado)
            );

            if ($puntaje <= $mejorPuntaje) {
                continue;
            }

            $mejorPuntaje = $puntaje;
            $mejor = [
                'lectura_publica' => 1,
                'lectura_fuente' => 'gutendex',
                'lectura_id_externo' => (string) ($resultado['id'] ?? ''),
                'lectura_url' => $fuente['url'],
                'lectura_formato' => $fuente['formato'],
            ];
        }

        if ($mejorPuntaje < 4 || $mejor === null) {
            return null;
        }

        return $mejor;
    }

    /**
     * @param array<string, mixed> $formatos
     * @return array{formato: string, url: string}|null
     */
    private function seleccionarFormatoLegible(array $formatos): ?array
    {
        $prioridades = [
            'text/plain; charset=utf-8',
            'text/plain; charset=us-ascii',
            'text/plain',
            'text/html; charset=utf-8',
            'text/html',
        ];

        foreach ($prioridades as $clave) {
            $url = trim((string) ($formatos[$clave] ?? ''));
            if ($url === '') {
                continue;
            }

            $urlSinQuery = strtolower((string) parse_url($url, PHP_URL_PATH));
            if (str_ends_with($urlSinQuery, '.zip')) {
                continue;
            }

            return ['formato' => $clave, 'url' => $url];
        }

        return null;
    }

    private function calcularPuntajeCoincidencia(
        string $tituloBase,
        string $autorBase,
        string $tituloResultado,
        string $autorResultado
    ): int {
        $puntaje = 0;

        if ($tituloResultado === $tituloBase) {
            $puntaje += 8;
        } elseif (
            ($tituloBase !== '' && str_contains($tituloResultado, $tituloBase))
            || ($tituloResultado !== '' && str_contains($tituloBase, $tituloResultado))
        ) {
            $puntaje += 5;
        }

        if ($autorBase !== '' && $autorResultado !== '' && str_contains($autorResultado, $autorBase)) {
            $puntaje += 3;
        } elseif ($autorBase !== '' && $autorResultado !== '') {
            $trozosAutorBase = array_filter(explode(' ', $autorBase), static fn (string $t): bool => mb_strlen($t) >= 4);
            foreach ($trozosAutorBase as $trozo) {
                if (str_contains($autorResultado, $trozo)) {
                    $puntaje += 1;
                }
            }
        }

        return $puntaje;
    }

    /**
     * @return array<int, string>
     */
    private function obtenerPaginasDesdeCache(
        string $cacheKey,
        string $url,
        string $formato,
        bool $validarComoEspanol
    ): array
    {
        $this->asegurarDirectorioCache();
        $rutaTexto = $this->directorioCacheLecturas . DIRECTORY_SEPARATOR . $cacheKey . '.txt';
        $rutaPaginas = $this->directorioCacheLecturas . DIRECTORY_SEPARATOR . $cacheKey . '.pages.json';

        if (!is_file($rutaTexto)) {
            $contenidoCrudo = $this->descargarContenido($url);
            $contenidoTexto = $this->normalizarContenidoTexto($contenidoCrudo, $formato, $validarComoEspanol);
            if (trim($contenidoTexto) === '') {
                throw new RuntimeException('La fuente publica no devolvio texto legible.');
            }

            file_put_contents($rutaTexto, $contenidoTexto);
        } else {
            $contenidoTexto = (string) file_get_contents($rutaTexto);
            if ($validarComoEspanol && !$this->pareceTextoEnEspanol($contenidoTexto)) {
                @unlink($rutaTexto);
                @unlink($rutaPaginas);
                throw new RuntimeException('La fuente publica encontrada no esta en espanol.');
            }
        }

        if (!is_file($rutaPaginas)) {
            $paginas = $this->dividirTextoEnPaginas($contenidoTexto);
            file_put_contents($rutaPaginas, json_encode($paginas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $jsonPaginas = (string) file_get_contents($rutaPaginas);
        $paginas = json_decode($jsonPaginas, true);
        if (!is_array($paginas)) {
            throw new RuntimeException('No se pudo interpretar la cache de paginas de lectura.');
        }

        $paginasLimpias = [];
        foreach ($paginas as $pagina) {
            if (!is_string($pagina)) {
                continue;
            }
            $textoPagina = trim($pagina);
            if ($textoPagina === '') {
                continue;
            }
            $paginasLimpias[] = $textoPagina;
        }

        if (count($paginasLimpias) === 0) {
            throw new RuntimeException('La cache de lectura esta vacia.');
        }

        return $paginasLimpias;
    }

    private function asegurarDirectorioCache(): void
    {
        if (is_dir($this->directorioCacheLecturas)) {
            return;
        }

        if (!mkdir($concurrentDirectory = $this->directorioCacheLecturas, 0777, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException('No se pudo crear directorio de cache de lectura.');
        }
    }

    private function descargarContenido(string $url): string
    {
        $url = trim($url);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('La URL de lectura publica no es valida.');
        }

        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl === false) {
                throw new RuntimeException('No se pudo iniciar cURL para lectura publica.');
            }

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 22,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Fabularia-Lector/1.0',
            ]);

            $respuesta = curl_exec($curl);
            $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($respuesta === false) {
                throw new RuntimeException('Fallo de red en lectura publica: ' . $error);
            }

            if ($codigo < 200 || $codigo >= 300) {
                throw new RuntimeException('La fuente publica devolvio HTTP ' . $codigo . '.');
            }
        } else {
            $contexto = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 22,
                    'ignore_errors' => true,
                    'header' => "User-Agent: Fabularia-Lector/1.0\r\n",
                ],
            ]);

            $respuesta = @file_get_contents($url, false, $contexto);
            if ($respuesta === false) {
                throw new RuntimeException('No se pudo descargar texto de lectura publica.');
            }
        }

        if (strlen($respuesta) > self::MAX_BYTES_DESCARGA) {
            $respuesta = substr($respuesta, 0, self::MAX_BYTES_DESCARGA);
        }

        if (!mb_check_encoding($respuesta, 'UTF-8')) {
            $respuesta = mb_convert_encoding($respuesta, 'UTF-8', 'auto');
        }

        return $respuesta;
    }

    private function normalizarContenidoTexto(string $contenidoCrudo, string $formato, bool $validarComoEspanol): string
    {
        $contenido = trim($contenidoCrudo);
        if ($contenido === '') {
            return '';
        }

        if (str_contains(mb_strtolower($formato), 'html')) {
            $contenido = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6])[^>]*>/i', "\n", $contenido) ?: $contenido;
            $contenido = strip_tags($contenido);
            $contenido = html_entity_decode($contenido, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } else {
            $contenido = html_entity_decode($contenido, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $contenido = str_replace(["\r\n", "\r"], "\n", $contenido);
        $contenido = preg_replace('/[ \t]+/u', ' ', $contenido) ?: $contenido;
        $contenido = preg_replace("/\n{3,}/u", "\n\n", $contenido) ?: $contenido;
        $contenido = trim($contenido);

        if ($validarComoEspanol && !$this->pareceTextoEnEspanol($contenido)) {
            throw new RuntimeException('La fuente publica encontrada no esta en espanol.');
        }

        return $contenido;
    }

    /**
     * @param array<string, mixed> $resultado
     * @return array<string, mixed>|null
     */
    private function normalizarLibroCatalogoLibre(array $resultado): ?array
    {
        $idioma = $this->idiomaPrincipalCatalogo($resultado['languages'] ?? []);
        if ($idioma === null) {
            return null;
        }

        $titulo = trim((string) ($resultado['title'] ?? ''));
        if ($titulo === '') {
            return null;
        }

        $autor = 'Autor desconocido';
        $autores = $resultado['authors'] ?? [];
        if (is_array($autores) && count($autores) > 0 && is_array($autores[0])) {
            $autorTemporal = trim((string) ($autores[0]['name'] ?? ''));
            if ($autorTemporal !== '') {
                $autor = $autorTemporal;
            }
        }

        $formatos = $resultado['formats'] ?? [];
        if (!is_array($formatos)) {
            return null;
        }

        $lectura = $this->seleccionarFormatoLegible($formatos);
        if ($lectura === null) {
            return null;
        }

        $portadaUrl = trim((string) ($formatos['image/jpeg'] ?? ''));
        if ($portadaUrl === '') {
            $portadaUrl = null;
        }

        $descripcion = '';
        $resumenes = $resultado['summaries'] ?? [];
        if (is_array($resumenes) && count($resumenes) > 0) {
            $descripcion = trim((string) ($resumenes[0] ?? ''));
        }

        if ($descripcion === '') {
            $temas = $resultado['subjects'] ?? [];
            if (is_array($temas) && count($temas) > 0) {
                $descripcion = trim(implode('. ', array_slice(array_filter(array_map(static fn ($tema): string => trim((string) $tema), $temas)), 0, 3)));
            }
        }

        return [
            'id_externo' => (string) ($resultado['id'] ?? ''),
            'titulo' => $titulo,
            'autor' => $autor,
            'genero' => 'Dominio publico',
            'descripcion' => $descripcion,
            'portada_url' => $portadaUrl,
            'idioma' => $idioma,
            'idioma_etiqueta' => strtoupper($idioma),
            'lectura_fuente' => 'gutendex',
            'lectura_formato' => $lectura['formato'],
            'lectura_url' => $lectura['url'],
        ];
    }

    /**
     * @param mixed $idiomas
     */
    private function idiomaPrincipalCatalogo(mixed $idiomas): ?string
    {
        if (!is_array($idiomas) || count($idiomas) === 0) {
            return null;
        }

        foreach ($idiomas as $idioma) {
            $codigo = trim((string) $idioma);
            if ($codigo === 'es') {
                return 'es';
            }
        }

        foreach ($idiomas as $idioma) {
            $codigo = trim((string) $idioma);
            if ($codigo === 'en') {
                return 'en';
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function dividirTextoEnPaginas(string $texto): array
    {
        $bloques = preg_split("/\n{2,}/u", trim($texto)) ?: [];
        $bloques = array_values(array_filter(array_map(static fn (string $b): string => trim($b), $bloques), static fn (string $b): bool => $b !== ''));

        if (count($bloques) === 0) {
            return ['Sin contenido disponible para lectura.'];
        }

        $paginas = [];
        $acumulado = '';

        foreach ($bloques as $bloque) {
            $bloquesSegmentados = $this->segmentarBloqueLargo($bloque);
            foreach ($bloquesSegmentados as $segmento) {
                $candidato = $acumulado === '' ? $segmento : ($acumulado . "\n\n" . $segmento);
                if (mb_strlen($candidato, 'UTF-8') > self::MAX_CHARS_PAGINA && $acumulado !== '') {
                    $paginas[] = $acumulado;
                    $acumulado = $segmento;
                } else {
                    $acumulado = $candidato;
                }
            }
        }

        if ($acumulado !== '') {
            $paginas[] = $acumulado;
        }

        if (count($paginas) === 0) {
            $paginas[] = 'Sin contenido disponible para lectura.';
        }

        return $paginas;
    }

    /**
     * @return array<int, string>
     */
    private function segmentarBloqueLargo(string $bloque): array
    {
        $bloque = trim($bloque);
        if ($bloque === '') {
            return [];
        }

        if (mb_strlen($bloque, 'UTF-8') <= self::MAX_CHARS_PAGINA) {
            return [$bloque];
        }

        $palabras = preg_split('/\s+/u', $bloque) ?: [];
        $segmentos = [];
        $actual = '';

        foreach ($palabras as $palabra) {
            $palabra = trim($palabra);
            if ($palabra === '') {
                continue;
            }

            $candidato = $actual === '' ? $palabra : ($actual . ' ' . $palabra);
            if (mb_strlen($candidato, 'UTF-8') > self::MAX_CHARS_PAGINA && $actual !== '') {
                $segmentos[] = $actual;
                $actual = $palabra;
            } else {
                $actual = $candidato;
            }
        }

        if ($actual !== '') {
            $segmentos[] = $actual;
        }

        return $segmentos;
    }

    /**
     * @return array<string, mixed>
     */
    private function obtenerJsonConReintento(string $url, int $intentos = 2): array
    {
        $intentos = max(1, min(4, $intentos));
        $ultimaExcepcion = null;

        for ($i = 1; $i <= $intentos; $i++) {
            try {
                return $this->obtenerJson($url);
            } catch (\Throwable $excepcion) {
                $ultimaExcepcion = $excepcion;
                if ($i < $intentos) {
                    usleep(300_000);
                }
            }
        }

        if ($ultimaExcepcion instanceof \Throwable) {
            throw $ultimaExcepcion;
        }

        throw new RuntimeException('No se pudo obtener JSON de API publica.');
    }

    /**
     * @return array<string, mixed>
     */
    private function obtenerJson(string $url): array
    {
        $cache = $this->cargarJsonDesdeCache($url, 900);
        if ($cache !== null) {
            return $cache;
        }

        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl === false) {
                throw new RuntimeException('No se pudo iniciar cURL para busqueda publica.');
            }

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Fabularia-Lector/1.0',
            ]);

            $respuesta = curl_exec($curl);
            $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($respuesta === false) {
                throw new RuntimeException('Fallo cURL: ' . $error);
            }

            if ($codigo < 200 || $codigo >= 300) {
                throw new RuntimeException('La API publica devolvio HTTP ' . $codigo);
            }
        } else {
            $contexto = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 20,
                    'ignore_errors' => true,
                    'header' => "User-Agent: Fabularia-Lector/1.0\r\n",
                ],
            ]);

            $respuesta = @file_get_contents($url, false, $contexto);
            if ($respuesta === false) {
                throw new RuntimeException('No se pudo obtener respuesta JSON de API publica.');
            }
        }

        $datos = json_decode($respuesta, true);
        if (!is_array($datos)) {
            throw new RuntimeException('Respuesta JSON invalida de API publica.');
        }

        $this->guardarJsonEnCache($url, $datos);
        return $datos;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function cargarJsonDesdeCache(string $url, int $ttlSegundos): ?array
    {
        try {
            $this->asegurarDirectorioCache();
            $ruta = $this->rutaCacheJson($url);
            if (!is_file($ruta)) {
                return null;
            }

            $modificacion = (int) (@filemtime($ruta) ?: 0);
            if ($modificacion <= 0 || (time() - $modificacion) > $ttlSegundos) {
                return null;
            }

            $contenido = (string) file_get_contents($ruta);
            $datos = json_decode($contenido, true);
            return is_array($datos) ? $datos : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function guardarJsonEnCache(string $url, array $datos): void
    {
        try {
            $this->asegurarDirectorioCache();
            $ruta = $this->rutaCacheJson($url);
            file_put_contents($ruta, json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable) {
            // Cache opcional.
        }
    }

    private function rutaCacheJson(string $url): string
    {
        return $this->directorioCacheLecturas . DIRECTORY_SEPARATOR . 'json-' . sha1($url) . '.json';
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]);
        $texto = preg_replace('/[^a-z0-9\s]/u', ' ', $texto) ?: $texto;
        $texto = preg_replace('/\s+/u', ' ', $texto) ?: $texto;
        return trim($texto);
    }

    /**
     * @param mixed $idiomas
     */
    private function incluyeIdiomaEspanol(mixed $idiomas): bool
    {
        if (!is_array($idiomas) || count($idiomas) === 0) {
            return false;
        }

        foreach ($idiomas as $idioma) {
            if (trim((string) $idioma) === 'es') {
                return true;
            }
        }

        return false;
    }

    private function pareceTextoEnEspanol(string $texto): bool
    {
        $muestra = mb_substr(mb_strtolower($texto, 'UTF-8'), 0, 16000, 'UTF-8');
        $palabras = preg_split('/[^a-záéíóúüñ]+/u', $muestra) ?: [];
        $palabras = array_values(array_filter($palabras, static fn (string $p): bool => $p !== ''));

        if (count($palabras) < 80) {
            return true;
        }

        $comunes = array_flip([
            'de', 'la', 'que', 'el', 'en', 'y', 'a', 'los', 'del', 'se', 'las',
            'por', 'un', 'para', 'con', 'no', 'una', 'su', 'al', 'lo', 'como',
            'mas', 'pero', 'sus', 'le', 'ya', 'o', 'fue', 'ha', 'si', 'porque',
            'esta', 'entre', 'cuando', 'muy', 'sin', 'sobre', 'tambien', 'me',
            'hasta', 'hay', 'donde', 'quien', 'desde', 'todo', 'nos', 'durante',
        ]);

        $aciertos = 0;
        foreach ($palabras as $palabra) {
            if (isset($comunes[$palabra])) {
                $aciertos++;
            }
        }

        $ratio = $aciertos / max(1, count($palabras));
        return $ratio >= 0.09;
    }
}
