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

    public function crearPrestamo(int $idLibro, int $idUsuarioDueno, int $idUsuarioPrestado): int
    {
        $sql = 'INSERT INTO prestamos (id_libro, id_usuario_dueno, id_usuario_prestado)
                VALUES (:id_libro, :id_usuario_dueno, :id_usuario_prestado)';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'id_libro' => $idLibro,
            'id_usuario_dueno' => $idUsuarioDueno,
            'id_usuario_prestado' => $idUsuarioPrestado,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function listarPrestamosDeUsuario(int $idUsuarioPrestado): array
    {
        $sql = 'SELECT p.id, p.fecha_prestamo, p.fecha_devolucion,
                       l.id AS id_libro, l.titulo, l.autor, l.portada_url,
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
}
