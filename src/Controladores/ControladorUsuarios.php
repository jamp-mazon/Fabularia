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
        $telefono = SolicitudHttp::obtenerTexto($datos, 'telefono');
        $telefono = $telefono === '' ? null : $telefono;
        $email = mb_strtolower(SolicitudHttp::obtenerTexto($datos, 'email'));
        $contrasena = SolicitudHttp::obtenerTexto($datos, 'contrasena');

        if ($nombre === '' || $apellidos === '' || $email === '' || $contrasena === '') {
            return [422, ['error' => 'Nombre, apellidos, email y contraseña son obligatorios.']];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [422, ['error' => 'El email no tiene un formato válido.']];
        }

        if ($telefono !== null && !$this->telefonoValido($telefono)) {
            return [422, ['error' => 'El telefono no tiene un formato valido.']];
        }

        if (mb_strlen($contrasena) < 6) {
            return [422, ['error' => 'La contraseña debe tener al menos 6 caracteres.']];
        }

        if ($this->repositorioUsuarios->obtenerPorEmail($email) !== null) {
            return [409, ['error' => 'Ya existe un usuario con ese email.']];
        }

        $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);
        $idUsuario = $this->repositorioUsuarios->crearUsuario(
            $nombre,
            $apellidos,
            $telefono,
            $email,
            $contrasenaHash
        );

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
                    'telefono' => $telefono,
                    'telegram_chat_id' => null,
                    'telegram_usuario' => null,
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
                    'telefono' => $usuario['telefono'],
                    'telegram_chat_id' => $usuario['telegram_chat_id'],
                    'telegram_usuario' => $usuario['telegram_usuario'],
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

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function cambiarContrasena(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            return [401, ['error' => 'Debes iniciar sesion para cambiar la contrasena.']];
        }

        $datos = SolicitudHttp::obtenerDatosEntrada();
        $contrasenaActual = SolicitudHttp::obtenerTexto($datos, 'contrasena_actual');
        $contrasenaNueva = SolicitudHttp::obtenerTexto($datos, 'contrasena_nueva');
        $confirmarContrasena = SolicitudHttp::obtenerTexto($datos, 'confirmar_contrasena');

        if ($contrasenaActual === '' || $contrasenaNueva === '' || $confirmarContrasena === '') {
            return [422, ['error' => 'Debes completar todos los campos de contrasena.']];
        }

        if ($contrasenaNueva !== $confirmarContrasena) {
            return [422, ['error' => 'La nueva contrasena y su confirmacion no coinciden.']];
        }

        if (mb_strlen($contrasenaNueva) < 6) {
            return [422, ['error' => 'La nueva contrasena debe tener al menos 6 caracteres.']];
        }

        $contrasenaHashActual = $this->repositorioUsuarios->obtenerContrasenaHashPorId($idUsuario);
        if ($contrasenaHashActual === null) {
            return [404, ['error' => 'No se encontro el usuario autenticado.']];
        }

        if (!password_verify($contrasenaActual, $contrasenaHashActual)) {
            return [401, ['error' => 'La contrasena actual es incorrecta.']];
        }

        if (password_verify($contrasenaNueva, $contrasenaHashActual)) {
            return [422, ['error' => 'La nueva contrasena no puede ser igual a la actual.']];
        }

        $actualizado = $this->repositorioUsuarios->actualizarContrasena(
            $idUsuario,
            password_hash($contrasenaNueva, PASSWORD_DEFAULT)
        );

        if (!$actualizado) {
            return [404, ['error' => 'No se pudo actualizar la contrasena del usuario.']];
        }

        $this->logger->info('Contrasena actualizada', ['id_usuario' => $idUsuario]);

        return [200, ['mensaje' => 'Contrasena actualizada correctamente.']];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function actualizarTelefono(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            return [401, ['error' => 'Debes iniciar sesion para actualizar el telefono.']];
        }

        $datos = SolicitudHttp::obtenerDatosEntrada();
        $telefono = SolicitudHttp::obtenerTexto($datos, 'telefono');
        $telefono = $telefono === '' ? null : $telefono;

        if ($telefono !== null && !$this->telefonoValido($telefono)) {
            return [422, ['error' => 'El telefono no tiene un formato valido.']];
        }

        $usuario = $this->repositorioUsuarios->obtenerPorId($idUsuario);
        if ($usuario === null) {
            return [404, ['error' => 'No se encontro el usuario autenticado.']];
        }

        $actualizado = $this->repositorioUsuarios->actualizarTelefono($idUsuario, $telefono);
        if (!$actualizado && (($usuario['telefono'] ?? null) !== $telefono)) {
            return [404, ['error' => 'No se pudo actualizar el telefono.']];
        }

        $this->logger->info('Telefono actualizado por el usuario', [
            'id_usuario' => $idUsuario,
            'telefono' => $telefono,
        ]);

        return [200, ['mensaje' => 'Telefono actualizado correctamente.', 'telefono' => $telefono]];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function desvincularTelegram(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            return [401, ['error' => 'Debes iniciar sesion para desvincular Telegram.']];
        }

        $usuario = $this->repositorioUsuarios->obtenerPorId($idUsuario);
        if ($usuario === null) {
            return [404, ['error' => 'No se encontro el usuario autenticado.']];
        }

        $this->repositorioUsuarios->desvincularTelegram($idUsuario);
        $this->logger->info('Telegram desvinculado por el usuario', ['id_usuario' => $idUsuario]);

        return [200, ['mensaje' => 'Telegram desvinculado correctamente.']];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function eliminarCuenta(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            return [401, ['error' => 'Debes iniciar sesion para eliminar la cuenta.']];
        }

        $datos = SolicitudHttp::obtenerDatosEntrada();
        $contrasena = SolicitudHttp::obtenerTexto($datos, 'contrasena');
        if ($contrasena === '') {
            return [422, ['error' => 'Debes indicar tu contrasena para eliminar la cuenta.']];
        }

        $contrasenaHashActual = $this->repositorioUsuarios->obtenerContrasenaHashPorId($idUsuario);
        if ($contrasenaHashActual === null) {
            return [404, ['error' => 'No se encontro el usuario autenticado.']];
        }

        if (!password_verify($contrasena, $contrasenaHashActual)) {
            return [401, ['error' => 'La contrasena es incorrecta.']];
        }

        $eliminado = $this->repositorioUsuarios->eliminarCuentaConDependencias($idUsuario);
        if (!$eliminado) {
            return [404, ['error' => 'No se pudo eliminar la cuenta solicitada.']];
        }

        unset($_SESSION['id_usuario'], $_SESSION['nombre_usuario']);
        $this->logger->info('Cuenta eliminada por el usuario', ['id_usuario' => $idUsuario]);

        return [200, ['mensaje' => 'Cuenta eliminada correctamente.']];
    }

    private function telefonoValido(string $telefono): bool
    {
        return preg_match('/^[0-9+()\\-\\s]{6,30}$/', $telefono) === 1;
    }
}
