<?php

declare(strict_types=1);

namespace Fabularia\Repositorios;

use PDO;

final class RepositorioPrestamos
{
    public function __construct(private readonly PDO $conexion)
    {
    }

    public function existePrestamoActivo(int $idLibro): bool
    {
        $sql = 'SELECT id FROM prestamos WHERE id_libro = :id_libro AND fecha_devolucion IS NULL LIMIT 1';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['id_libro' => $idLibro]);
        return (bool) $sentencia->fetch();
    }

    /**
     * @param array<string, mixed> $lectura
     */
    public function crearPrestamo(
        int $idLibro,
        int $idUsuarioDueno,
        int $idUsuarioPrestado,
        array $lectura = []
    ): int
    {
        $sql = 'INSERT INTO prestamos (
                    id_libro,
                    id_usuario_dueno,
                    id_usuario_prestado,
                    lectura_publica,
                    lectura_fuente,
                    lectura_id_externo,
                    lectura_url,
                    lectura_formato,
                    pagina_lectura_actual,
                    paginas_lectura_totales
                )
                VALUES (
                    :id_libro,
                    :id_usuario_dueno,
                    :id_usuario_prestado,
                    :lectura_publica,
                    :lectura_fuente,
                    :lectura_id_externo,
                    :lectura_url,
                    :lectura_formato,
                    1,
                    NULL
                )';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'id_libro' => $idLibro,
            'id_usuario_dueno' => $idUsuarioDueno,
            'id_usuario_prestado' => $idUsuarioPrestado,
            'lectura_publica' => (int) ($lectura['lectura_publica'] ?? 0),
            'lectura_fuente' => $lectura['lectura_fuente'] ?? null,
            'lectura_id_externo' => $lectura['lectura_id_externo'] ?? null,
            'lectura_url' => $lectura['lectura_url'] ?? null,
            'lectura_formato' => $lectura['lectura_formato'] ?? null,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function listarPrestamosDeUsuario(int $idUsuarioPrestado): array
    {
        $sql = 'SELECT p.id, p.fecha_prestamo, p.fecha_devolucion,
                       p.lectura_publica, p.lectura_fuente, p.lectura_id_externo, p.lectura_url, p.lectura_formato,
                       p.pagina_lectura_actual, p.paginas_lectura_totales, p.fecha_actualizacion_lectura,
                       l.id AS id_libro, l.titulo, l.autor, l.genero, l.descripcion, l.portada_url,
                       CONCAT(u.nombre, " ", u.apellidos) AS nombre_dueno
                FROM prestamos p
                INNER JOIN libros l ON l.id = p.id_libro
                INNER JOIN usuarios u ON u.id = p.id_usuario_dueno
                WHERE p.id_usuario_prestado = :id_usuario_prestado
                ORDER BY p.fecha_prestamo DESC';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['id_usuario_prestado' => $idUsuarioPrestado]);
        return $sentencia->fetchAll();
    }

    public function obtenerPrestamoDeUsuario(int $idPrestamo, int $idUsuarioPrestado): ?array
    {
        $sql = 'SELECT p.id, p.id_libro, p.fecha_prestamo, p.fecha_devolucion,
                       p.lectura_publica, p.lectura_fuente, p.lectura_id_externo, p.lectura_url, p.lectura_formato,
                       p.pagina_lectura_actual, p.paginas_lectura_totales, p.fecha_actualizacion_lectura,
                       l.titulo, l.autor, l.genero, l.descripcion, l.portada_url,
                       CONCAT(u.nombre, " ", u.apellidos) AS nombre_dueno
                FROM prestamos p
                INNER JOIN libros l ON l.id = p.id_libro
                INNER JOIN usuarios u ON u.id = p.id_usuario_dueno
                WHERE p.id = :id_prestamo
                  AND p.id_usuario_prestado = :id_usuario_prestado
                LIMIT 1';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'id_prestamo' => $idPrestamo,
            'id_usuario_prestado' => $idUsuarioPrestado,
        ]);
        $fila = $sentencia->fetch();
        return is_array($fila) ? $fila : null;
    }

    public function devolverPrestamo(int $idPrestamo, int $idUsuarioPrestado): bool
    {
        $sql = 'UPDATE prestamos
                SET fecha_devolucion = NOW()
                WHERE id = :id_prestamo
                  AND id_usuario_prestado = :id_usuario_prestado
                  AND fecha_devolucion IS NULL';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'id_prestamo' => $idPrestamo,
            'id_usuario_prestado' => $idUsuarioPrestado,
        ]);

        return $sentencia->rowCount() > 0;
    }

    public function actualizarProgresoLectura(
        int $idPrestamo,
        int $idUsuarioPrestado,
        int $paginaActual,
        int $paginasTotales
    ): bool {
        $sql = 'UPDATE prestamos
                SET pagina_lectura_actual = :pagina_lectura_actual,
                    paginas_lectura_totales = :paginas_lectura_totales,
                    fecha_actualizacion_lectura = NOW()
                WHERE id = :id_prestamo
                  AND id_usuario_prestado = :id_usuario_prestado
                  AND fecha_devolucion IS NULL';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'id_prestamo' => $idPrestamo,
            'id_usuario_prestado' => $idUsuarioPrestado,
            'pagina_lectura_actual' => max(1, $paginaActual),
            'paginas_lectura_totales' => max(1, $paginasTotales),
        ]);

        return $sentencia->rowCount() > 0;
    }

    /**
     * @param array<string, mixed> $lectura
     */
    public function actualizarFuenteLectura(int $idPrestamo, int $idUsuarioPrestado, array $lectura): bool
    {
        $sql = 'UPDATE prestamos
                SET lectura_publica = :lectura_publica,
                    lectura_fuente = :lectura_fuente,
                    lectura_id_externo = :lectura_id_externo,
                    lectura_url = :lectura_url,
                    lectura_formato = :lectura_formato,
                    pagina_lectura_actual = 1,
                    paginas_lectura_totales = NULL,
                    fecha_actualizacion_lectura = NOW()
                WHERE id = :id_prestamo
                  AND id_usuario_prestado = :id_usuario_prestado
                  AND fecha_devolucion IS NULL';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'id_prestamo' => $idPrestamo,
            'id_usuario_prestado' => $idUsuarioPrestado,
            'lectura_publica' => (int) ($lectura['lectura_publica'] ?? 0),
            'lectura_fuente' => $lectura['lectura_fuente'] ?? null,
            'lectura_id_externo' => $lectura['lectura_id_externo'] ?? null,
            'lectura_url' => $lectura['lectura_url'] ?? null,
            'lectura_formato' => $lectura['lectura_formato'] ?? null,
        ]);

        return $sentencia->rowCount() > 0;
    }
}
