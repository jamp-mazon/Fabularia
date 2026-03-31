<?php

declare(strict_types=1);

namespace Fabularia\Repositorios;

use PDO;

final class RepositorioUsuarios
{
    public function __construct(private readonly PDO $conexion)
    {
    }

    public function crearUsuario(string $nombre, string $apellidos, string $email, string $contrasenaHash): int
    {
        $sql = 'INSERT INTO usuarios (nombre, apellidos, email, contrasena_hash)
                VALUES (:nombre, :apellidos, :email, :contrasena_hash)';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'email' => $email,
            'contrasena_hash' => $contrasenaHash,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function obtenerPorEmail(string $email): ?array
    {
        $sql = 'SELECT id, nombre, apellidos, email, contrasena_hash
                FROM usuarios
                WHERE email = :email
                LIMIT 1';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['email' => $email]);
        $fila = $sentencia->fetch();

        return is_array($fila) ? $fila : null;
    }

    public function obtenerPorId(int $idUsuario): ?array
    {
        $sql = 'SELECT id, nombre, apellidos, email, fecha_registro
                FROM usuarios
                WHERE id = :id
                LIMIT 1';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['id' => $idUsuario]);
        $fila = $sentencia->fetch();

        return is_array($fila) ? $fila : null;
    }
}
