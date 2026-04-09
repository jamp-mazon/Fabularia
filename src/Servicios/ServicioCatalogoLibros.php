<?php

declare(strict_types=1);

namespace Fabularia\Servicios;

use Monolog\Logger;
use RuntimeException;

final class ServicioCatalogoLibros
{
    public function __construct(
        private readonly Logger $logger,
        private readonly ?string $apiKeyGoogleBooks = null
    ) {
    }

    public function buscarSugerencias(string $textoBusqueda, int $maxResultados = 8): array
    {
        $textoBusqueda = trim($textoBusqueda);
        if (mb_strlen($textoBusqueda) < 2) {
            return [];
        }

        $maxResultados = max(1, min(12, $maxResultados));
        $url = $this->construirUrlGoogleBooks($textoBusqueda, $maxResultados);

        try {
            $datos = $this->obtenerJson($url);
            $items = $datos['items'] ?? [];
            if (!is_array($items)) {
                return [];
            }

            $sugerencias = [];
            $clavesVistas = [];

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $sugerencia = $this->normalizarItemGoogleBooks($item);
                if ($sugerencia === null) {
                    continue;
                }

                $clave = mb_strtolower($sugerencia['titulo'] . '|' . $sugerencia['autor']);
                if (isset($clavesVistas[$clave])) {
                    continue;
                }
                $clavesVistas[$clave] = true;

                $sugerencias[] = $sugerencia;
            }

            return $sugerencias;
        } catch (RuntimeException $excepcion) {
            $this->logger->warning('Error consultando catalogo global de libros', [
                'mensaje' => $excepcion->getMessage(),
            ]);
            return [];
        }
    }

    private function construirUrlGoogleBooks(string $textoBusqueda, int $maxResultados): string
    {
        $q = rawurlencode('intitle:' . $textoBusqueda);
        $url = 'https://www.googleapis.com/books/v1/volumes'
            . '?q=' . $q
            . '&langRestrict=es'
            . '&printType=books'
            . '&maxResults=' . $maxResultados;

        if ($this->apiKeyGoogleBooks !== null && trim($this->apiKeyGoogleBooks) !== '') {
            $url .= '&key=' . rawurlencode(trim($this->apiKeyGoogleBooks));
        }

        return $url;
    }

    /**
     * @return array<string, mixed>
     */
    private function obtenerJson(string $url): array
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl === false) {
                throw new RuntimeException('No se pudo iniciar cURL para catalogo.');
            }

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
            ]);

            $respuesta = curl_exec($curl);
            $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($respuesta === false) {
                throw new RuntimeException('Fallo cURL en catalogo: ' . $error);
            }

            if ($codigo < 200 || $codigo >= 300) {
                throw new RuntimeException('Catalogo remoto devolvio HTTP ' . $codigo);
            }
        } else {
            $contexto = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'ignore_errors' => true,
                ],
            ]);

            $respuesta = @file_get_contents($url, false, $contexto);
            if ($respuesta === false) {
                throw new RuntimeException('No se pudo obtener respuesta del catalogo.');
            }
        }

        $datos = json_decode($respuesta, true);
        if (!is_array($datos)) {
            throw new RuntimeException('Respuesta JSON invalida del catalogo.');
        }

        return $datos;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, string|null>|null
     */
    private function normalizarItemGoogleBooks(array $item): ?array
    {
        $volumeInfo = $item['volumeInfo'] ?? null;
        if (!is_array($volumeInfo)) {
            return null;
        }

        $titulo = trim((string) ($volumeInfo['title'] ?? ''));
        if ($titulo === '') {
            return null;
        }

        $autores = $volumeInfo['authors'] ?? [];
        $autor = is_array($autores) && count($autores) > 0 ? trim((string) $autores[0]) : 'Autor desconocido';

        $categorias = $volumeInfo['categories'] ?? [];
        $genero = is_array($categorias) && count($categorias) > 0 ? trim((string) $categorias[0]) : '';
        if ($genero === '') {
            $genero = 'General';
        }

        $descripcion = trim((string) ($volumeInfo['description'] ?? ''));
        if ($descripcion === '') {
            $searchInfo = $item['searchInfo'] ?? null;
            if (is_array($searchInfo)) {
                $descripcion = trim(strip_tags((string) ($searchInfo['textSnippet'] ?? '')));
            }
        }

        $imageLinks = $volumeInfo['imageLinks'] ?? [];
        $portada = null;
        if (is_array($imageLinks)) {
            $portada = (string) ($imageLinks['thumbnail'] ?? $imageLinks['smallThumbnail'] ?? '');
            $portada = trim($portada);
            if ($portada === '') {
                $portada = null;
            } elseif (str_starts_with($portada, 'http://')) {
                $portada = 'https://' . substr($portada, 7);
            }
        }

        return [
            'id_externo' => (string) ($item['id'] ?? ''),
            'titulo' => $titulo,
            'autor' => $autor,
            'genero' => $genero,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'portada_url' => $portada,
        ];
    }
}
