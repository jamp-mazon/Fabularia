<?php

declare(strict_types=1);

namespace Fabularia\Servicios;

use Monolog\Logger;
use RuntimeException;
use Throwable;

final class ServicioWebhookPrestamos
{
    public function __construct(
        private readonly Logger $logger,
        private readonly string $urlWebhook
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function notificarPrestamoCreado(array $payload): void
    {
        if (trim($this->urlWebhook) === '') {
            return;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('No se pudo serializar el payload de notificacion.');
        }

        try {
            if (function_exists('curl_init')) {
                $this->enviarConCurl($json);
                return;
            }

            $this->enviarConStreamContext($json);
        } catch (Throwable $excepcion) {
            $this->logger->warning('No se pudo enviar webhook de prestamo a n8n', [
                'mensaje' => $excepcion->getMessage(),
            ]);
        }
    }

    private function enviarConCurl(string $json): void
    {
        $curl = curl_init($this->urlWebhook);
        if ($curl === false) {
            throw new RuntimeException('No se pudo inicializar cURL.');
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $json,
        ]);

        $respuesta = curl_exec($curl);
        $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($respuesta === false) {
            throw new RuntimeException('Fallo cURL al enviar webhook: ' . $error);
        }

        if ($codigo < 200 || $codigo >= 300) {
            throw new RuntimeException('Respuesta HTTP no valida del webhook: ' . $codigo);
        }
    }

    private function enviarConStreamContext(string $json): void
    {
        $contexto = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $json,
                'timeout' => 8,
                'ignore_errors' => true,
            ],
        ]);

        $respuesta = @file_get_contents($this->urlWebhook, false, $contexto);
        $cabecerasRespuesta = $http_response_header ?? [];
        $primeraCabecera = $cabecerasRespuesta[0] ?? '';
        $codigo = 0;

        if (preg_match('/\s(\d{3})\s/', $primeraCabecera, $coincidencias) === 1) {
            $codigo = (int) $coincidencias[1];
        }

        if ($respuesta === false || $codigo < 200 || $codigo >= 300) {
            throw new RuntimeException('No se recibio confirmacion valida del webhook.');
        }
    }
}
