<?php

declare(strict_types=1);

namespace Fabularia\Servicios;

final class NormalizadorGeneroLibros
{
    /**
     * @var array<string, string>
     */
    private const MAPA_GENEROS = [
        'general' => 'General',
        'ficcion' => 'Ficcion',
        'fiction' => 'Ficcion',
        'no ficcion' => 'No ficcion',
        'nonfiction' => 'No ficcion',
        'non fiction' => 'No ficcion',
        'novela' => 'Novela',
        'novel' => 'Novela',
        'romance' => 'Romance',
        'aventura' => 'Aventura',
        'adventure' => 'Aventura',
        'misterio' => 'Misterio',
        'mystery' => 'Misterio',
        'thriller' => 'Thriller',
        'thrillers' => 'Thriller',
        'terror' => 'Terror',
        'horror' => 'Terror',
        'fantasia' => 'Fantasia',
        'fantasy' => 'Fantasia',
        'ciencia ficcion' => 'Ciencia ficcion',
        'science fiction' => 'Ciencia ficcion',
        'science fiction fantasy' => 'Ciencia ficcion y fantasia',
        'scifi' => 'Ciencia ficcion',
        'sci fi' => 'Ciencia ficcion',
        'historia' => 'Historia',
        'history' => 'Historia',
        'ficcion historica' => 'Ficcion historica',
        'historical fiction' => 'Ficcion historica',
        'poesia' => 'Poesia',
        'poetry' => 'Poesia',
        'biografia' => 'Biografia',
        'biography' => 'Biografia',
        'autobiografia' => 'Autobiografia',
        'autobiography' => 'Autobiografia',
        'biography autobiography' => 'Biografia y autobiografia',
        'biografia autobiografia' => 'Biografia y autobiografia',
        'juvenile fiction' => 'Ficcion juvenil',
        'juvenile nonfiction' => 'No ficcion juvenil',
        'juvenil ficcion' => 'Ficcion juvenil',
        'ficcion juvenil' => 'Ficcion juvenil',
        'juvenil no ficcion' => 'No ficcion juvenil',
        'no ficcion juvenil' => 'No ficcion juvenil',
        'young adult fiction' => 'Ficcion juvenil',
        'young adult nonfiction' => 'No ficcion juvenil',
        'children' => 'Infantil',
        'kids' => 'Infantil',
        'infantil' => 'Infantil',
        'comics' => 'Comics',
        'graphic novels' => 'Novela grafica',
        'comic books strips' => 'Comics',
        'comics graphic novels' => 'Comics y novela grafica',
        'drama' => 'Drama',
        'humor' => 'Humor',
        'familia' => 'Familia',
        'family' => 'Familia',
        'relaciones' => 'Relaciones',
        'relationship' => 'Relaciones',
        'relationships' => 'Relaciones',
        'family relationships' => 'Familia y relaciones',
        'familia relaciones' => 'Familia y relaciones',
        'deportes ocio' => 'Deportes y ocio',
        'deportes recreacion' => 'Deportes y ocio',
        'sport' => 'Deportes',
        'sports' => 'Deportes',
        'recreation' => 'Ocio',
        'recreacion' => 'Ocio',
        'sports recreation' => 'Deportes y ocio',
        'sports and recreation' => 'Deportes y ocio',
        'sports amp recreation' => 'Deportes y ocio',
        'viajes' => 'Viajes',
        'travel' => 'Viajes',
        'autoayuda' => 'Autoayuda',
        'self help' => 'Autoayuda',
        'psicologia' => 'Psicologia',
        'psychology' => 'Psicologia',
        'arte' => 'Arte',
        'art' => 'Arte',
        'musica' => 'Musica',
        'music' => 'Musica',
        'religion' => 'Religion',
        'filosofia' => 'Filosofia',
        'philosophy' => 'Filosofia',
    ];

    public static function normalizarParaGuardar(string $genero): string
    {
        $genero = trim($genero);
        if ($genero === '') {
            return 'General';
        }

        $partes = preg_split('/\s*(?:[\/|,;]+|&| and | y )\s*/u', $genero) ?: [];
        $resultado = [];

        foreach ($partes as $parte) {
            $parte = trim($parte);
            if ($parte === '') {
                continue;
            }

            $resultado[] = self::traducirParte($parte);
        }

        if (empty($resultado)) {
            return 'General';
        }

        $resultadoSinDuplicados = [];
        foreach ($resultado as $valor) {
            $resultadoSinDuplicados[$valor] = true;
        }

        return implode(' / ', array_keys($resultadoSinDuplicados));
    }

    private static function traducirParte(string $texto): string
    {
        $clave = self::normalizarClave($texto);
        if ($clave === '') {
            return 'General';
        }

        if (isset(self::MAPA_GENEROS[$clave])) {
            return self::MAPA_GENEROS[$clave];
        }

        // Inferencia por palabras para cubrir categorias de Google Books no mapeadas exactas.
        if (str_contains($clave, 'juvenile') && str_contains($clave, 'fiction')) {
            return 'Ficcion juvenil';
        }

        if (
            (str_contains($clave, 'juvenile') || str_contains($clave, 'young adult'))
            && (str_contains($clave, 'nonfiction') || str_contains($clave, 'non fiction'))
        ) {
            return 'No ficcion juvenil';
        }

        if (str_contains($clave, 'biography') && str_contains($clave, 'autobiography')) {
            return 'Biografia y autobiografia';
        }

        if (str_contains($clave, 'family') && str_contains($clave, 'relationship')) {
            return 'Familia y relaciones';
        }

        if (str_contains($clave, 'sports') && str_contains($clave, 'recreation')) {
            return 'Deportes y ocio';
        }

        if (str_contains($clave, 'science') && str_contains($clave, 'fiction')) {
            return 'Ciencia ficcion';
        }

        if (str_contains($clave, 'fiction') || str_contains($clave, 'novel')) {
            return 'Ficcion';
        }

        if (str_contains($clave, 'nonfiction') || str_contains($clave, 'non fiction')) {
            return 'No ficcion';
        }

        if (str_contains($clave, 'sports') || str_contains($clave, 'sport')) {
            return 'Deportes';
        }

        if (str_contains($clave, 'recreation')) {
            return 'Ocio';
        }

        if (str_contains($clave, 'family')) {
            return 'Familia';
        }

        if (str_contains($clave, 'relationship')) {
            return 'Relaciones';
        }

        return trim($texto);
    }

    private static function normalizarClave(string $texto): string
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
            'Ã¡' => 'a',
            'Ã©' => 'e',
            'Ã­' => 'i',
            'Ã³' => 'o',
            'Ãº' => 'u',
            'Ã¼' => 'u',
            'Ã±' => 'n',
            'ÃƒÂ¡' => 'a',
            'ÃƒÂ©' => 'e',
            'ÃƒÂ­' => 'i',
            'ÃƒÂ³' => 'o',
            'ÃƒÂº' => 'u',
            'ÃƒÂ¼' => 'u',
            'ÃƒÂ±' => 'n',
        ]);

        $texto = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $texto) ?? '';
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? '';

        return trim($texto);
    }
}