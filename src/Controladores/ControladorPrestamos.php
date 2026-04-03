<?php

declare(strict_types=1);

namespace Fabularia\Controladores;

use Fabularia\Http\SolicitudHttp;
use Fabularia\Repositorios\RepositorioLibros;
use Fabularia\Repositorios\RepositorioPrestamos;
use Fabularia\Repositorios\RepositorioUsuarios;
use Fabularia\Servicios\ServicioWebhookPrestamos;
use Monolog\Logger;

final class ControladorPrestamos
{
    public function __construct(
        private readonly RepositorioPrestamos $repositorioPrestamos,
        private readonly RepositorioLibros $repositorioLibros,
        private readonly RepositorioUsuarios $repositorioUsuarios,
        private readonly ServicioWebhookPrestamos $servicioWebhookPrestamos,
        private readonly Logger $logger
    ) {
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function solicitarPrestamo(): array
    {
        $idUsuarioPrestado = (int) ($_SESSION['id_usuario'] ?? 0);
        $datos = SolicitudHttp::obtenerDatosEntrada();
        $idLibro = SolicitudHttp::obtenerEntero($datos, 'id_libro');

        if ($idLibro <= 0) {
            return [422, ['error' => 'Debes indicar un id_libro válido.']];
        }

        $libro = $this->repositorioLibros->obtenerPorId($idLibro);
        if ($libro === null) {
            return [404, ['error' => 'El libro solicitado no existe.']];
        }

        $idUsuarioDueno = (int) $libro['id_usuario'];
        if ($idUsuarioDueno === $idUsuarioPrestado) {
            return [400, ['error' => 'No puedes pedir prestado tu propio libro.']];
        }

        if ((int) $libro['activo_intercambio'] !== 1) {
            return [409, ['error' => 'Este libro ya no está disponible para intercambio.']];
        }

        if ($this->repositorioPrestamos->existePrestamoActivo($idLibro)) {
            return [409, ['error' => 'El libro ya está prestado actualmente.']];
        }

        $idPrestamo = $this->repositorioPrestamos->crearPrestamo($idLibro, $idUsuarioDueno, $idUsuarioPrestado);
        $this->logger->info('Préstamo creado', [
            'id_prestamo' => $idPrestamo,
            'id_libro' => $idLibro,
            'id_usuario_dueno' => $idUsuarioDueno,
            'id_usuario_prestado' => $idUsuarioPrestado,
        ]);

        $this->enviarNotificacionWebhook($idPrestamo, $libro, $idUsuarioDueno, $idUsuarioPrestado);

        return [201, ['mensaje' => 'Préstamo solicitado correctamente.', 'id_prestamo' => $idPrestamo]];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function listarMisPrestamos(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        $prestamos = $this->repositorioPrestamos->listarPrestamosDeUsuario($idUsuario);
        return [200, ['prestamos' => $prestamos]];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function devolverPrestamo(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        $datos = SolicitudHttp::obtenerDatosEntrada();
        $idPrestamo = SolicitudHttp::obtenerEntero($datos, 'id_prestamo');

        if ($idPrestamo <= 0) {
            return [422, ['error' => 'Debes indicar un id_prestamo válido.']];
        }

        $actualizado = $this->repositorioPrestamos->devolverPrestamo($idPrestamo, $idUsuario);
        if (!$actualizado) {
            return [404, ['error' => 'No se encontró un préstamo activo para devolver con ese id.']];
        }

        $this->logger->info('Préstamo devuelto', ['id_prestamo' => $idPrestamo, 'id_usuario' => $idUsuario]);
        return [200, ['mensaje' => 'Préstamo devuelto correctamente.']];
    }

    /**
     * @param array<string, mixed> $libro
     */
    private function enviarNotificacionWebhook(
        int $idPrestamo,
        array $libro,
        int $idUsuarioDueno,
        int $idUsuarioPrestado
    ): void {
        $usuarioDueno = $this->repositorioUsuarios->obtenerPorId($idUsuarioDueno);
        $usuarioPrestado = $this->repositorioUsuarios->obtenerPorId($idUsuarioPrestado);

        if ($usuarioDueno === null || $usuarioPrestado === null) {
            $this->logger->warning('No se pudo construir payload n8n por usuarios inexistentes', [
                'id_prestamo' => $idPrestamo,
            ]);
            return;
        }

        $payload = [
            'evento' => 'prestamo_creado',
            'fecha_evento' => date(DATE_ATOM),
            'prestamo' => [
                'id' => $idPrestamo,
            ],
            'libro' => [
                'id' => (int) ($libro['id'] ?? 0),
                'titulo' => (string) ($libro['titulo'] ?? ''),
            ],
            'usuario_dueno' => [
                'id' => (int) $usuarioDueno['id'],
                'nombre' => trim((string) $usuarioDueno['nombre'] . ' ' . (string) $usuarioDueno['apellidos']),
                'email' => (string) $usuarioDueno['email'],
                'telefono' => $usuarioDueno['telefono'],
                'telegram_chat_id' => $usuarioDueno['telegram_chat_id'],
                'telegram_usuario' => $usuarioDueno['telegram_usuario'],
            ],
            'usuario_receptor' => [
                'id' => (int) $usuarioPrestado['id'],
                'nombre' => trim((string) $usuarioPrestado['nombre'] . ' ' . (string) $usuarioPrestado['apellidos']),
                'email' => (string) $usuarioPrestado['email'],
                'telefono' => $usuarioPrestado['telefono'],
                'telegram_chat_id' => $usuarioPrestado['telegram_chat_id'],
                'telegram_usuario' => $usuarioPrestado['telegram_usuario'],
            ],
        ];

        $this->servicioWebhookPrestamos->notificarPrestamoCreado($payload);
    }
}
