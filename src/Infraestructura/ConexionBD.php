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

        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Inyección de TLS si las variables están presentes
        if (isset($_ENV['DB_SSL_CA'])) {
            $opciones[PDO::MYSQL_ATTR_SSL_CA] = $_ENV['DB_SSL_CA'];
            $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;

            if (isset($_ENV['DB_SSL_CERT'], $_ENV['DB_SSL_KEY'])) {
                $opciones[PDO::MYSQL_ATTR_SSL_CERT] = $_ENV['DB_SSL_CERT'];
                $opciones[PDO::MYSQL_ATTR_SSL_KEY] = $_ENV['DB_SSL_KEY'];
            }
        }

        try {
            return new PDO($dsn, $usuario, $contrasena, $opciones);
        } catch (PDOException $excepcion) {
            throw new RuntimeException(
                'No se pudo conectar con la base de datos. Revisa la configuración TLS/Env.',
                0,
                $excepcion
            );
        }
    }
}