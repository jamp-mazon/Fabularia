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
        $datos = $this->normalizarDatosEntrada(SolicitudHttp::obtenerDatosEntrada());
        $tokenRecibido = trim((string) (
            $_SERVER['HTTP_X_VINCULACION_TOKEN']
            ?? $_SERVER['HTTP_X_TELEGRAM_VINCULACION_TOKEN']
            ?? $_GET['token_vinculacion']
            ?? $_GET['token']
            ?? ''
        ));
        if ($tokenRecibido === '') {
            $tokenRecibido = $this->obtenerTextoFlexible($datos, [
                'token_vinculacion',
                'vinculacion_token',
                'token',
                'body.token_vinculacion',
                'body.vinculacion_token',
                'body.token',
                'json.token_vinculacion',
                'json.vinculacion_token',
                'json.token',
                'data.token_vinculacion',
                'data.vinculacion_token',
                'data.token',
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

        $respuestaStart = $this->vincularDesdeComandoStart($datos);
        if ($respuestaStart !== null) {
            return $respuestaStart;
        }

        $idUsuario = $this->obtenerEnteroFlexible($datos, [
            'usuario_id',
            'id_usuario',
            'user_id',
            'body.usuario_id',
            'body.id_usuario',
            'body.user_id',
            'json.usuario_id',
            'json.id_usuario',
            'json.user_id',
            'data.usuario_id',
            'data.id_usuario',
            'data.user_id',
        ]);
        if ($idUsuario <= 0) {
            $idUsuario = $this->obtenerUsuarioIdDesdeStart($datos);
        }

        $telegramChatId = $this->obtenerChatIdTelegram($datos);
        $telegramUsuario = $this->obtenerUsuarioTelegram($datos);
        $telegramUsuario = $telegramUsuario === '' ? null : $telegramUsuario;

        if ($idUsuario <= 0 || $telegramChatId === '') {
            $this->logger->warning('No se pudo vincular Telegram: faltan datos obligatorios.', [
                'usuario_id' => $idUsuario,
                'telegram_chat_id_recibido' => $telegramChatId !== '',
                'claves_recibidas' => implode(',', array_slice(array_keys($datos), 0, 12)),
            ]);
            return [422, ['error' => 'usuario_id y telegram_chat_id son obligatorios.']];
        }

        return $this->guardarVinculacionTelegram($idUsuario, $telegramChatId, $telegramUsuario, 'payload_n8n');
    }

    /**
     * @param array<string, mixed> $datos
     * @return array{0: int, 1: array<string, mixed>}|null
     */
    private function vincularDesdeComandoStart(array $datos): ?array
    {
        $textoTelegram = $this->obtenerTextoTelegram($datos);
        if ($textoTelegram === '') {
            return null;
        }

        if (preg_match('/^\\/start(?:@\\w+)?(?:\\s|$)/', $textoTelegram) !== 1) {
            return null;
        }

        $chatIdTelegram = $this->obtenerChatIdTelegram($datos);
        $usuarioTelegram = $this->obtenerUsuarioTelegram($datos);
        $firstNameTelegram = $this->obtenerFirstNameTelegram($datos);
        $nombreGuardable = $usuarioTelegram !== '' ? $usuarioTelegram : ($firstNameTelegram !== '' ? $firstNameTelegram : null);

        if (preg_match('/^\\/start(?:@\\w+)?$/', $textoTelegram) === 1) {
            $this->logger->warning('Telegram /start recibido sin id de usuario Fabularia.', [
                'telegram_chat_id_recibido' => $chatIdTelegram !== '',
                'telegram_usuario_recibido' => $usuarioTelegram !== '',
                'first_name_recibido' => $firstNameTelegram !== '',
            ]);

            return [
                422,
                [
                    'error' => 'Abre Telegram desde el enlace de Fabularia para vincular tu usuario correctamente.',
                ],
            ];
        }

        if (preg_match('/^\\/start(?:@\\w+)?\\s+(\\d+)$/', $textoTelegram, $coincidencias) !== 1) {
            return [422, ['error' => 'El comando /start no contiene un id de usuario valido.']];
        }

        if ($chatIdTelegram === '') {
            return [422, ['error' => 'No se ha recibido chat_id de Telegram.']];
        }

        $idUsuarioTelegram = (int) ($coincidencias[1] ?? 0);
        return $this->guardarVinculacionTelegram($idUsuarioTelegram, $chatIdTelegram, $nombreGuardable, 'comando_start', [
            'telegram_usuario_recibido' => $usuarioTelegram !== '',
            'first_name_recibido' => $firstNameTelegram !== '',
        ]);
    }

    /**
     * @param array<string, mixed> $contextoLog
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function guardarVinculacionTelegram(
        int $idUsuario,
        string $telegramChatId,
        ?string $telegramUsuario,
        string $origen,
        array $contextoLog = []
    ): array {
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

        $usuarioActualizado = $this->repositorioUsuarios->obtenerPorId($idUsuario);
        if ($usuarioActualizado === null || trim((string) ($usuarioActualizado['telegram_chat_id'] ?? '')) === '') {
            $this->logger->warning('La vinculacion Telegram no quedo persistida en base de datos.', [
                'id_usuario' => $idUsuario,
            ]);
            return [500, ['error' => 'No se pudo guardar la vinculacion Telegram.']];
        }

        $this->logger->info('Cuenta Telegram vinculada', [
            'id_usuario' => $idUsuario,
            'telegram_chat_id' => $telegramChatId,
            'origen' => $origen,
        ] + $contextoLog);

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
     */
    private function obtenerTextoTelegram(array $datos): string
    {
        return $this->obtenerTextoFlexible($datos, [
            'text',
            'message.text',
            'callback_query.message.text',
            'body.text',
            'body.message.text',
            'body.callback_query.message.text',
            'json.text',
            'json.message.text',
            'json.callback_query.message.text',
            'data.text',
            'data.message.text',
            'data.callback_query.message.text',
        ]);
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function obtenerChatIdTelegram(array $datos): string
    {
        return $this->obtenerTextoFlexible($datos, [
            'telegram_chat_id',
            'chat_id',
            'message.chat.id',
            'callback_query.message.chat.id',
            'body.telegram_chat_id',
            'body.chat_id',
            'body.message.chat.id',
            'body.callback_query.message.chat.id',
            'json.telegram_chat_id',
            'json.chat_id',
            'json.message.chat.id',
            'json.callback_query.message.chat.id',
            'data.telegram_chat_id',
            'data.chat_id',
            'data.message.chat.id',
            'data.callback_query.message.chat.id',
        ]);
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function obtenerUsuarioTelegram(array $datos): string
    {
        return $this->obtenerTextoFlexible($datos, [
            'telegram_usuario',
            'username',
            'message.from.username',
            'message.chat.username',
            'callback_query.from.username',
            'body.telegram_usuario',
            'body.username',
            'body.message.from.username',
            'body.message.chat.username',
            'body.callback_query.from.username',
            'json.telegram_usuario',
            'json.username',
            'json.message.from.username',
            'json.message.chat.username',
            'json.callback_query.from.username',
            'data.telegram_usuario',
            'data.username',
            'data.message.from.username',
            'data.message.chat.username',
            'data.callback_query.from.username',
        ]);
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function obtenerFirstNameTelegram(array $datos): string
    {
        return $this->obtenerTextoFlexible($datos, [
            'first_name',
            'message.from.first_name',
            'message.chat.first_name',
            'callback_query.from.first_name',
            'body.first_name',
            'body.message.from.first_name',
            'body.message.chat.first_name',
            'body.callback_query.from.first_name',
            'json.first_name',
            'json.message.from.first_name',
            'json.message.chat.first_name',
            'json.callback_query.from.first_name',
            'data.first_name',
            'data.message.from.first_name',
            'data.message.chat.first_name',
            'data.callback_query.from.first_name',
        ]);
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
            'body.text',
            'body.message.text',
            'body.callback_query.message.text',
            'json.text',
            'json.message.text',
            'json.callback_query.message.text',
            'data.text',
            'data.message.text',
            'data.callback_query.message.text',
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
     * n8n puede entregar el update plano o envolverlo en body/json/data. Si el envoltorio llega
     * como string JSON, lo decodificamos para poder leer rutas como body.message.chat.id.
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function normalizarDatosEntrada(array $datos): array
    {
        foreach (['body', 'json', 'data'] as $clave) {
            if (!array_key_exists($clave, $datos) || !is_string($datos[$clave])) {
                continue;
            }

            $decodificado = json_decode(trim($datos[$clave]), true);
            if (is_array($decodificado)) {
                $datos[$clave] = $decodificado;
            }
        }

        return $datos;
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
