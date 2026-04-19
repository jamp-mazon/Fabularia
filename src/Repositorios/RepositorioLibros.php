<?php

declare(strict_types=1);

namespace Fabularia\Repositorios;

use Fabularia\Servicios\NormalizadorGeneroLibros;
use PDO;

final class RepositorioLibros
{
    public function __construct(private readonly PDO $conexion)
    {
    }

    public function crearLibro(
        int $idUsuario,
        string $titulo,
        string $autor,
        string $genero,
        ?string $portadaUrl,
        ?string $descripcion
    ): int {
        $sql = 'INSERT INTO libros (id_usuario, titulo, autor, genero, portada_url, descripcion, activo_intercambio)
                VALUES (:id_usuario, :titulo, :autor, :genero, :portada_url, :descripcion, 1)';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'id_usuario' => $idUsuario,
            'titulo' => $titulo,
            'autor' => $autor,
            'genero' => $genero,
            'portada_url' => $portadaUrl,
            'descripcion' => $descripcion,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function obtenerPorId(int $idLibro): ?array
    {
        $sql = 'SELECT l.id, l.id_usuario, l.titulo, l.autor, l.genero, l.portada_url, l.descripcion, l.activo_intercambio,
                       CONCAT(u.nombre, " ", u.apellidos) AS nombre_propietario
                FROM libros l
                INNER JOIN usuarios u ON u.id = l.id_usuario
                WHERE l.id = :id_libro
                LIMIT 1';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['id_libro' => $idLibro]);
        $fila = $sentencia->fetch();

        return is_array($fila) ? $fila : null;
    }

    public function listarDisponibles(
        string $terminoBusqueda = '',
        string $genero = '',
        ?int $idUsuarioActual = null
    ): array {
        $terminoLike = '%' . $terminoBusqueda . '%';

        /*
         * Esta consulta resuelve disponibilidad real:
         * 1) Toma libros activos para intercambio.
         * 2) Hace LEFT JOIN con prestamos activos (sin fecha_devolucion).
         * 3) Filtra p.id IS NULL para dejar solo libros no prestados.
         * 4) Aplica busqueda por titulo/autor y filtro por genero.
         * 5) Opcionalmente excluye libros propios.
         */
        $sql = 'SELECT l.id, l.titulo, l.autor, l.genero, l.portada_url, l.descripcion, l.id_usuario,
                       CONCAT(u.nombre, " ", u.apellidos) AS propietario
                FROM libros l
                INNER JOIN usuarios u ON u.id = l.id_usuario
                LEFT JOIN prestamos p ON p.id_libro = l.id AND p.fecha_devolucion IS NULL
                WHERE l.activo_intercambio = 1
                  AND p.id IS NULL';

        $parametros = [];

        if ($terminoBusqueda !== '') {
            $sql .= ' AND (l.titulo LIKE :termino_titulo OR l.autor LIKE :termino_autor)';
            $parametros['termino_titulo'] = $terminoLike;
            $parametros['termino_autor'] = $terminoLike;
        }

        if ($genero !== '') {
            $variantesGenero = $this->obtenerVariantesGenero($genero);
            $condicionesGenero = [];

            foreach ($variantesGenero as $indice => $variante) {
                $claveParametro = 'genero_like_' . $indice;
                $condicionesGenero[] = 'l.genero LIKE :' . $claveParametro;
                $parametros[$claveParametro] = '%' . $variante . '%';
            }

            if (!empty($condicionesGenero)) {
                $sql .= ' AND (' . implode(' OR ', $condicionesGenero) . ')';
            }
        }

        if ($idUsuarioActual !== null) {
            $sql .= ' AND l.id_usuario <> :id_usuario_actual';
            $parametros['id_usuario_actual'] = $idUsuarioActual;
        }

        $sql .= ' ORDER BY l.fecha_publicacion DESC';
        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute($parametros);
        return $sentencia->fetchAll();
    }

    /**
     * @return string[]
     */
    private function obtenerVariantesGenero(string $textoGenero): array
    {
        $textoOriginal = trim($textoGenero);
        $textoNormalizado = $this->normalizarTexto($textoOriginal);
        $textoTraducido = NormalizadorGeneroLibros::normalizarParaGuardar($textoOriginal);
        $textoTraducidoNormalizado = $this->normalizarTexto($textoTraducido);

        $variantes = [
            $textoOriginal,
            $textoNormalizado,
            $textoTraducido,
            $textoTraducidoNormalizado,
        ];

        $partesTraducidas = preg_split('/\s*\/\s*/u', $textoTraducido) ?: [];
        foreach ($partesTraducidas as $parteTraducida) {
            $parteTraducida = trim($parteTraducida);
            if ($parteTraducida === '') {
                continue;
            }
            $variantes[] = $parteTraducida;
            $variantes[] = $this->normalizarTexto($parteTraducida);
        }

        $equivalencias = [
            'juvenil' => ['juvenile', 'young adult', 'kids', 'children'],
            'juvenile' => ['juvenil', 'infantil'],
            'infantil' => ['children', 'kids', 'juvenile'],
            'ficcion' => ['fiction'],
            'fiction' => ['ficcion'],
            'no ficcion' => ['nonfiction', 'non fiction'],
            'nonfiction' => ['no ficcion'],
            'fantasia' => ['fantasy'],
            'fantasy' => ['fantasia'],
            'ciencia ficcion' => ['science fiction', 'sci-fi', 'scifi'],
            'science fiction' => ['ciencia ficcion'],
            'biografia' => ['biography'],
            'biography' => ['biografia'],
            'autobiografia' => ['autobiography'],
            'autobiography' => ['autobiografia'],
            'poesia' => ['poetry'],
            'poetry' => ['poesia'],
            'aventura' => ['adventure'],
            'adventure' => ['aventura'],
            'historico' => ['historical', 'history'],
            'historical' => ['historico'],
            'historia' => ['history', 'historical'],
            'romance' => ['romance'],
            'terror' => ['horror'],
            'horror' => ['terror'],
            'deportes' => ['sports', 'sport', 'recreation', 'sports recreation'],
            'sport' => ['deportes'],
            'sports' => ['deportes'],
            'recreation' => ['ocio', 'deportes'],
            'ocio' => ['recreation', 'sports'],
            'familia' => ['family', 'relationships'],
            'relaciones' => ['relationship', 'relationships', 'family'],
            'family' => ['familia', 'relaciones'],
            'relationships' => ['relaciones', 'familia'],
        ];

        foreach ($equivalencias as $clave => $sinonimos) {
            if (!str_contains($textoNormalizado, $clave)) {
                continue;
            }

            foreach ($sinonimos as $sinonimo) {
                $variantes[] = $sinonimo;
            }
        }

        $palabras = preg_split('/\s+/', $textoNormalizado) ?: [];
        foreach ($palabras as $palabra) {
            if ($palabra === '') {
                continue;
            }

            if (!array_key_exists($palabra, $equivalencias)) {
                continue;
            }

            foreach ($equivalencias[$palabra] as $sinonimo) {
                $variantes[] = $sinonimo;
            }
        }

        $variantesUnicas = [];
        foreach ($variantes as $variante) {
            $valor = trim((string) $variante);
            if ($valor === '') {
                continue;
            }
            $variantesUnicas[$valor] = true;
        }

        return array_keys($variantesUnicas);
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));

        return strtr(
            $texto,
            [
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
            ]
        );
    }

    public function listarPorUsuario(int $idUsuario): array
    {
        $sql = 'SELECT l.id, l.titulo, l.autor, l.genero, l.portada_url, l.descripcion,
                       CASE WHEN p.id IS NULL THEN 1 ELSE 0 END AS disponible
                FROM libros l
                LEFT JOIN prestamos p ON p.id_libro = l.id AND p.fecha_devolucion IS NULL
                WHERE l.id_usuario = :id_usuario
                ORDER BY l.fecha_publicacion DESC';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['id_usuario' => $idUsuario]);

        return $sentencia->fetchAll();
    }

    public function existePrestamoActivoPorLibro(int $idLibro): bool
    {
        $sql = 'SELECT id
                FROM prestamos
                WHERE id_libro = :id_libro
                  AND fecha_devolucion IS NULL
                LIMIT 1';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['id_libro' => $idLibro]);

        return (bool) $sentencia->fetch();
    }

    public function eliminarLibroDeUsuario(int $idLibro, int $idUsuario): bool
    {
        $sql = 'DELETE FROM libros
                WHERE id = :id_libro
                  AND id_usuario = :id_usuario';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'id_libro' => $idLibro,
            'id_usuario' => $idUsuario,
        ]);

        return $sentencia->rowCount() > 0;
    }
}