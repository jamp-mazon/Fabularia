<?php

declare(strict_types=1);

namespace Fabularia\Controladores;

use Fabularia\Http\SolicitudHttp;
use Fabularia\Repositorios\RepositorioUsuarios;
use Monolog\Logger;

final class ControladorUsuarios
{
    public function __construct(
        private readonly RepositorioUsuarios $repositorioUsuarios,
        private readonly Logger $logger
    ) {
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function registrar(): array
    {
        $datos = SolicitudHttp::obtenerDatosEntrada();
        $nombre = SolicitudHttp::obtenerTexto($datos, 'nombre');
        $apellidos = SolicitudHttp::obtenerTexto($datos, 'apellidos');
        $email = mb_strtolower(SolicitudHttp::obtenerTexto($datos, 'email'));
        $contrasena = SolicitudHttp::obtenerTexto($datos, 'contrasena');

        if ($nombre === '' || $apellidos === '' || $email === '' || $contrasena === '') {
            return [422, ['error' => 'Nombre, apellidos, email y contraseña son obligatorios.']];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [422, ['error' => 'El email no tiene un formato válido.']];
        }

        if (mb_strlen($contrasena) < 6) {
            return [422, ['error' => 'La contraseña debe tener al menos 6 caracteres.']];
        }

        if ($this->repositorioUsuarios->obtenerPorEmail($email) !== null) {
            return [409, ['error' => 'Ya existe un usuario con ese email.']];
        }

        $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);
        $idUsuario = $this->repositorioUsuarios->crearUsuario($nombre, $apellidos, $email, $contrasenaHash);

        $_SESSION['id_usuario'] = $idUsuario;
        $_SESSION['nombre_usuario'] = trim($nombre . ' ' . $apellidos);

        $this->logger->info('Usuario registrado', ['id_usuario' => $idUsuario, 'email' => $email]);

        return [
            201,
            [
                'mensaje' => 'Usuario registrado correctamente.',
                'usuario' => [
                    'id' => $idUsuario,
                    'nombre' => $nombre,
                    'apellidos' => $apellidos,
                    'email' => $email,
                ],
            ],
        ];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function iniciarSesion(): array
    {
        $datos = SolicitudHttp::obtenerDatosEntrada();
        $email = mb_strtolower(SolicitudHttp::obtenerTexto($datos, 'email'));
        $contrasena = SolicitudHttp::obtenerTexto($datos, 'contrasena');

        if ($email === '' || $contrasena === '') {
            return [422, ['error' => 'Debes indicar email y contraseña.']];
        }

        $usuario = $this->repositorioUsuarios->obtenerPorEmail($email);
        if ($usuario === null || !password_verify($contrasena, (string) $usuario['contrasena_hash'])) {
            return [401, ['error' => 'Credenciales incorrectas.']];
        }

        $_SESSION['id_usuario'] = (int) $usuario['id'];
        $_SESSION['nombre_usuario'] = trim((string) $usuario['nombre'] . ' ' . (string) $usuario['apellidos']);

        $this->logger->info('Inicio de sesión correcto', ['id_usuario' => (int) $usuario['id']]);

        return [
            200,
            [
                'mensaje' => 'Sesión iniciada.',
                'usuario' => [
                    'id' => (int) $usuario['id'],
                    'nombre' => (string) $usuario['nombre'],
                    'apellidos' => (string) $usuario['apellidos'],
                    'email' => (string) $usuario['email'],
                ],
            ],
        ];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function cerrarSesion(): array
    {
        unset($_SESSION['id_usuario'], $_SESSION['nombre_usuario']);
        return [200, ['mensaje' => 'Sesión cerrada correctamente.']];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function usuarioActual(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            return [200, ['autenticado' => false]];
        }

        $usuario = $this->repositorioUsuarios->obtenerPorId($idUsuario);
        if ($usuario === null) {
            unset($_SESSION['id_usuario'], $_SESSION['nombre_usuario']);
            return [200, ['autenticado' => false]];
        }

        return [200, ['autenticado' => true, 'usuario' => $usuario]];
    }
}
