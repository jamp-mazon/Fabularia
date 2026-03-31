<?php

declare(strict_types=1);

namespace Fabularia\Repositorios;

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
        ?string $descripcion
    ): int {
        $sql = 'INSERT INTO libros (id_usuario, titulo, autor, genero, descripcion, activo_intercambio)
                VALUES (:id_usuario, :titulo, :autor, :genero, :descripcion, 1)';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'id_usuario' => $idUsuario,
            'titulo' => $titulo,
            'autor' => $autor,
            'genero' => $genero,
            'descripcion' => $descripcion,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function obtenerPorId(int $idLibro): ?array
    {
        $sql = 'SELECT l.id, l.id_usuario, l.titulo, l.autor, l.genero, l.descripcion, l.activo_intercambio,
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
        $sql = 'SELECT l.id, l.titulo, l.autor, l.genero, l.descripcion, l.id_usuario,
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
            $sql .= ' AND l.genero = :genero';
            $parametros['genero'] = $genero;
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

    public function listarPorUsuario(int $idUsuario): array
    {
        $sql = 'SELECT l.id, l.titulo, l.autor, l.genero, l.descripcion,
                       CASE WHEN p.id IS NULL THEN 1 ELSE 0 END AS disponible
                FROM libros l
                LEFT JOIN prestamos p ON p.id_libro = l.id AND p.fecha_devolucion IS NULL
                WHERE l.id_usuario = :id_usuario
                ORDER BY l.fecha_publicacion DESC';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['id_usuario' => $idUsuario]);

        return $sentencia->fetchAll();
    }
}
