<?php

declare(strict_types=1);

namespace Fabularia\Controladores;

use Fabularia\Http\SolicitudHttp;
use Fabularia\Repositorios\RepositorioUsuarios;
use Monolog\Logger;
use PDOException;

final class ControladorTelegram
{
    public function __construct(
        private readonly RepositorioUsuarios $repositorioUsuarios,
        private readonly Logger $logger,
        private readonly string $tokenVinculacion
    ) {
    }

    /**
     * Endpoint para que n8n registre el chat_id de Telegram tras recibir /start USUARIO_ID.
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function vincularCuenta(): array
    {
        $datos = SolicitudHttp::obtenerDatosEntrada();
        $tokenRecibido = trim((string) ($_SERVER['HTTP_X_VINCULACION_TOKEN'] ?? ''));
        if ($tokenRecibido === '') {
            $tokenRecibido = SolicitudHttp::obtenerTexto($datos, 'token_vinculacion');
        }

        if (trim($this->tokenVinculacion) === '') {
            return [500, ['error' => 'Token de vinculacion Telegram no configurado en el servidor.']];
        }

        if (!hash_equals($this->tokenVinculacion, $tokenRecibido)) {
            return [401, ['error' => 'Token de vinculacion invalido.']];
        }

        $idUsuario = SolicitudHttp::obtenerEntero($datos, 'usuario_id');
        $telegramChatId = trim((string) ($datos['telegram_chat_id'] ?? $datos['chat_id'] ?? ''));
        $telegramUsuario = SolicitudHttp::obtenerTexto($datos, 'telegram_usuario');
        if ($telegramUsuario === '') {
            $telegramUsuario = SolicitudHttp::obtenerTexto($datos, 'username');
        }
        $telegramUsuario = $telegramUsuario === '' ? null : $telegramUsuario;

        if ($idUsuario <= 0 || $telegramChatId === '') {
            return [422, ['error' => 'usuario_id y telegram_chat_id son obligatorios.']];
        }

        $usuario = $this->repositorioUsuarios->obtenerPorId($idUsuario);
        if ($usuario === null) {
            return [404, ['error' => 'No existe usuario para vincular Telegram.']];
        }

        try {
            $this->repositorioUsuarios->vincularTelegram($idUsuario, $telegramChatId, $telegramUsuario);
        } catch (PDOException $excepcion) {
            if ($excepcion->getCode() === '23000') {
                return [409, ['error' => 'Ese telegram_chat_id ya esta vinculado a otro usuario.']];
            }
            throw $excepcion;
        }

        $this->logger->info('Cuenta Telegram vinculada', [
            'id_usuario' => $idUsuario,
            'telegram_chat_id' => $telegramChatId,
        ]);

        return [
            200,
            [
                'mensaje' => 'Cuenta Telegram vinculada correctamente.',
                'vinculacion' => [
                    'id_usuario' => $idUsuario,
                    'telegram_chat_id' => $telegramChatId,
                    'telegram_usuario' => $telegramUsuario,
                ],
            ],
        ];
    }
}
