<?php

declare(strict_types=1);

namespace Fabularia\Infraestructura;

use PDO;
use PDOException;
use RuntimeException;

final class ConexionBD
{
    public static function crearDesdeEntorno(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $puerto = $_ENV['DB_PORT'] ?? '3306';
        $nombreBase = $_ENV['DB_NAME'] ?? 'fabularia';
        $usuario = $_ENV['DB_USER'] ?? 'root';
        $contrasena = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host={$host};port={$puerto};dbname={$nombreBase};charset=utf8mb4";

        try {
            return new PDO(
                $dsn,
                $usuario,
                $contrasena,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $excepcion) {
            throw new RuntimeException(
                'No se pudo conectar con la base de datos. Revisa el archivo .env.',
                0,
                $excepcion
            );
        }
    }
}
