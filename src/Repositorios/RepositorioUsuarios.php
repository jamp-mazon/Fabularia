<?php

declare(strict_types=1);

namespace Fabularia\Repositorios;

use PDO;

final class RepositorioUsuarios
{
    private bool $tablaRestablecimientoAsegurada = false;

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

    public function crearTokenRestablecimiento(
        int $idUsuario,
        string $tokenHash,
        string $fechaExpiracion,
        ?string $ipSolicitud
    ): int {
        $this->asegurarTablaRestablecimiento();

        $sql = 'INSERT INTO usuarios_restablecimiento_contrasena
                    (id_usuario, token_hash, fecha_expiracion, ip_solicitud)
                VALUES (:id_usuario, :token_hash, :fecha_expiracion, :ip_solicitud)';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            'id_usuario' => $idUsuario,
            'token_hash' => $tokenHash,
            'fecha_expiracion' => $fechaExpiracion,
            'ip_solicitud' => $ipSolicitud,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function invalidarTokensActivosPorUsuario(int $idUsuario): void
    {
        $this->asegurarTablaRestablecimiento();

        $sql = 'UPDATE usuarios_restablecimiento_contrasena
                SET fecha_usado = NOW()
                WHERE id_usuario = :id_usuario
                  AND fecha_usado IS NULL
                  AND fecha_expiracion >= NOW()';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['id_usuario' => $idUsuario]);
    }

    public function obtenerTokenRestablecimientoValido(string $tokenHash): ?array
    {
        $this->asegurarTablaRestablecimiento();

        $sql = 'SELECT id, id_usuario, fecha_expiracion
                FROM usuarios_restablecimiento_contrasena
                WHERE token_hash = :token_hash
                  AND fecha_usado IS NULL
                  AND fecha_expiracion >= NOW()
                LIMIT 1';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['token_hash' => $tokenHash]);
        $fila = $sentencia->fetch();

        return is_array($fila) ? $fila : null;
    }

    public function marcarTokenRestablecimientoComoUsado(int $idToken): bool
    {
        $this->asegurarTablaRestablecimiento();

        $sql = 'UPDATE usuarios_restablecimiento_contrasena
                SET fecha_usado = NOW()
                WHERE id = :id
                  AND fecha_usado IS NULL';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(['id' => $idToken]);

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

    private function asegurarTablaRestablecimiento(): void
    {
        if ($this->tablaRestablecimientoAsegurada) {
            return;
        }

        $sql = 'CREATE TABLE IF NOT EXISTS usuarios_restablecimiento_contrasena (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    id_usuario INT UNSIGNED NOT NULL,
                    token_hash CHAR(64) NOT NULL,
                    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    fecha_expiracion DATETIME NOT NULL,
                    fecha_usado DATETIME NULL,
                    ip_solicitud VARCHAR(45) NULL,
                    CONSTRAINT fk_usuarios_restablecimiento_usuario
                        FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
                        ON DELETE CASCADE,
                    UNIQUE KEY ux_usuarios_restablecimiento_token_hash (token_hash),
                    KEY idx_usuarios_restablecimiento_usuario (id_usuario),
                    KEY idx_usuarios_restablecimiento_expiracion (fecha_expiracion)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $this->conexion->exec($sql);
        $this->tablaRestablecimientoAsegurada = true;
    }
}
