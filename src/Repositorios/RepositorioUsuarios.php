<?php

declare(strict_types=1);

namespace Fabularia\Repositorios;

use PDO;

final class RepositorioUsuarios
{
    public function __construct(private readonly PDO $conexion)
    {
    }

    public function crearUsuario(
        string $nombre,
        string $apellidos,
        ?string $telefono,
        string $email,
        string $contrasenaHash
    ): int
    {
        $sql = 'INSERT INTO usuarios (nombre, apellidos, telefono, email, contrasena_hash)
                VALUES (:nombre, :apellidos, :telefono, :email, :contrasena_hash)';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'telefono' => $telefono,
            'email' => $email,
            'contrasena_hash' => $contrasenaHash,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function obtenerPorEmail(string $email): ?array
    {
        $sql = 'SELECT id, nombre, apellidos, telefono, telegram_chat_id, telegram_usuario, email, contrasena_hash
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
        $sql = 'SELECT id, nombre, apellidos, telefono, telegram_chat_id, telegram_usuario, email, fecha_registro
                FROM usuarios
                WHERE id = :id
                LIMIT 1';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['id' => $idUsuario]);
        $fila = $sentencia->fetch();

        return is_array($fila) ? $fila : null;
    }

    public function vincularTelegram(int $idUsuario, string $telegramChatId, ?string $telegramUsuario): bool
    {
        $sql = 'UPDATE usuarios
                SET telegram_chat_id = :telegram_chat_id,
                    telegram_usuario = :telegram_usuario
                WHERE id = :id_usuario';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'telegram_chat_id' => $telegramChatId,
            'telegram_usuario' => $telegramUsuario,
            'id_usuario' => $idUsuario,
        ]);

        return $sentencia->rowCount() > 0;
    }

    public function obtenerContrasenaHashPorId(int $idUsuario): ?string
    {
        $sql = 'SELECT contrasena_hash
                FROM usuarios
                WHERE id = :id_usuario
                LIMIT 1';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['id_usuario' => $idUsuario]);
        $fila = $sentencia->fetch();

        if (!is_array($fila) || !isset($fila['contrasena_hash'])) {
            return null;
        }

        return (string) $fila['contrasena_hash'];
    }

    public function actualizarContrasena(int $idUsuario, string $contrasenaHash): bool
    {
        $sql = 'UPDATE usuarios
                SET contrasena_hash = :contrasena_hash
                WHERE id = :id_usuario';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'contrasena_hash' => $contrasenaHash,
            'id_usuario' => $idUsuario,
        ]);

        return $sentencia->rowCount() > 0;
    }

    public function actualizarTelefono(int $idUsuario, ?string $telefono): bool
    {
        $sql = 'UPDATE usuarios
                SET telefono = :telefono
                WHERE id = :id_usuario';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'telefono' => $telefono,
            'id_usuario' => $idUsuario,
        ]);

        return $sentencia->rowCount() > 0;
    }

    public function desvincularTelegram(int $idUsuario): bool
    {
        $sql = 'UPDATE usuarios
                SET telegram_chat_id = NULL,
                    telegram_usuario = NULL
                WHERE id = :id_usuario';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['id_usuario' => $idUsuario]);

        return $sentencia->rowCount() > 0;
    }

    public function eliminarCuentaConDependencias(int $idUsuario): bool
    {
        $this->conexion->beginTransaction();

        try {
            /*
             * Si se elimina un usuario con prestamos historicos, los FK RESTRICT
             * de prestamos impedirian el borrado. Por eso se limpian primero las
             * referencias de prestamos donde participa (dueno o receptor).
             */
            $sqlPrestamos = 'DELETE FROM prestamos
                             WHERE id_usuario_dueno = :id_usuario
                                OR id_usuario_prestado = :id_usuario';
            $sentenciaPrestamos = $this->conexion->prepare($sqlPrestamos);
            $sentenciaPrestamos->execute(['id_usuario' => $idUsuario]);

            $sqlUsuario = 'DELETE FROM usuarios
                           WHERE id = :id_usuario';
            $sentenciaUsuario = $this->conexion->prepare($sqlUsuario);
            $sentenciaUsuario->execute(['id_usuario' => $idUsuario]);

            $eliminado = $sentenciaUsuario->rowCount() > 0;
            $this->conexion->commit();

            return $eliminado;
        } catch (\Throwable $excepcion) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $excepcion;
        }
    }
}
