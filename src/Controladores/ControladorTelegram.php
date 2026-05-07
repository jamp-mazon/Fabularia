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
            $tokenRecibido = $this->obtenerTextoFlexible($datos, [
                'token_vinculacion',
                'vinculacion_token',
                'token',
            ]);
        }

        if (trim($this->tokenVinculacion) === '') {
            $this->logger->warning('No se pudo vincular Telegram: token no configurado.');
            return [500, ['error' => 'Token de vinculacion Telegram no configurado en el servidor.']];
        }

        if (!hash_equals($this->tokenVinculacion, $tokenRecibido)) {
            $this->logger->warning('No se pudo vincular Telegram: token invalido o ausente.', [
                'token_recibido' => $tokenRecibido !== '',
                'claves_recibidas' => implode(',', array_slice(array_keys($datos), 0, 12)),
            ]);
            return [401, ['error' => 'Token de vinculacion invalido.']];
        }

        $idUsuario = $this->obtenerEnteroFlexible($datos, [
            'usuario_id',
            'id_usuario',
            'user_id',
        ]);
        if ($idUsuario <= 0) {
            $idUsuario = $this->obtenerUsuarioIdDesdeStart($datos);
        }

        $telegramChatId = $this->obtenerTextoFlexible($datos, [
            'telegram_chat_id',
            'chat_id',
            'message.chat.id',
            'callback_query.message.chat.id',
        ]);
        $telegramUsuario = $this->obtenerTextoFlexible($datos, [
            'telegram_usuario',
            'username',
            'message.from.username',
            'callback_query.from.username',
        ]);
        $telegramUsuario = $telegramUsuario === '' ? null : $telegramUsuario;

        if ($idUsuario <= 0 || $telegramChatId === '') {
            $this->logger->warning('No se pudo vincular Telegram: faltan datos obligatorios.', [
                'usuario_id' => $idUsuario,
                'telegram_chat_id_recibido' => $telegramChatId !== '',
                'claves_recibidas' => implode(',', array_slice(array_keys($datos), 0, 12)),
            ]);
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
                $this->logger->warning('No se pudo vincular Telegram: chat_id ya vinculado.', [
                    'id_usuario' => $idUsuario,
                ]);
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

    /**
     * @param array<string, mixed> $datos
     * @param array<int, string> $claves
     */
    private function obtenerTextoFlexible(array $datos, array $claves): string
    {
        foreach ($claves as $clave) {
            $valor = $this->obtenerValorPorRuta($datos, $clave);
            if (is_scalar($valor)) {
                $texto = trim((string) $valor);
                if ($texto !== '') {
                    return $texto;
                }
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $datos
     * @param array<int, string> $claves
     */
    private function obtenerEnteroFlexible(array $datos, array $claves): int
    {
        foreach ($claves as $clave) {
            $valor = $this->obtenerValorPorRuta($datos, $clave);
            if (is_scalar($valor) && trim((string) $valor) !== '') {
                return (int) $valor;
            }
        }

        return 0;
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function obtenerUsuarioIdDesdeStart(array $datos): int
    {
        $texto = $this->obtenerTextoFlexible($datos, [
            'text',
            'message.text',
            'callback_query.message.text',
        ]);

        if ($texto === '') {
            return 0;
        }

        if (preg_match('/(?:^|\\s)\\/start(?:\\s+|=)(\\d+)/', $texto, $coincidencias) !== 1) {
            return 0;
        }

        return (int) ($coincidencias[1] ?? 0);
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function obtenerValorPorRuta(array $datos, string $ruta): mixed
    {
        $partes = explode('.', $ruta);
        $valor = $datos;

        foreach ($partes as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) {
                return null;
            }

            $valor = $valor[$parte];
        }

        return $valor;
    }
}
