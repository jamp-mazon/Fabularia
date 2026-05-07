<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$raizProyecto = dirname(__DIR__);
if (is_file($raizProyecto . '/.env')) {
    Dotenv::createImmutable($raizProyecto)->safeLoad();
}

$tokenBot = trim((string) ($_ENV['TELEGRAM_BOT_TOKEN'] ?? ''));
$secretoWebhook = trim((string) ($_ENV['TELEGRAM_WEBHOOK_SECRET'] ?? $_ENV['TELEGRAM_VINCULACION_TOKEN'] ?? ''));
$urlBaseAplicacion = trim((string) ($_ENV['APP_URL_BASE'] ?? ''));
$urlWebhook = trim((string) ($argv[1] ?? ''));

if ($urlWebhook === '') {
    if ($urlBaseAplicacion === '') {
        fwrite(STDERR, "Debes configurar APP_URL_BASE o pasar la URL del webhook como argumento.\n");
        fwrite(STDERR, "Ejemplo: php scripts/configurar_webhook_telegram.php https://dominio.com/api/telegram/webhook\n");
        exit(2);
    }

    $urlWebhook = rtrim($urlBaseAplicacion, '/') . '/api/telegram/webhook';
}

if ($tokenBot === '') {
    fwrite(STDERR, "Falta TELEGRAM_BOT_TOKEN en .env.\n");
    exit(2);
}

if ($secretoWebhook === '') {
    fwrite(STDERR, "Falta TELEGRAM_WEBHOOK_SECRET en .env.\n");
    exit(2);
}

if (preg_match('/^[A-Za-z0-9_-]{1,256}$/', $secretoWebhook) !== 1) {
    fwrite(STDERR, "TELEGRAM_WEBHOOK_SECRET solo debe usar letras, numeros, guion y guion bajo. No uses el token del bot como secret.\n");
    exit(2);
}

$respuestaSet = llamarTelegram($tokenBot, 'setWebhook', [
    'url' => $urlWebhook,
    'secret_token' => $secretoWebhook,
    'drop_pending_updates' => 'true',
    'allowed_updates' => json_encode(['message'], JSON_THROW_ON_ERROR),
]);

$respuestaInfo = llamarTelegram($tokenBot, 'getWebhookInfo');

echo "Webhook configurado para: {$urlWebhook}\n";
echo "setWebhook:\n" . json_encode($respuestaSet, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
echo "getWebhookInfo:\n" . json_encode($respuestaInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

/**
 * @param array<string, string> $parametros
 * @return array<string, mixed>
 */
function llamarTelegram(string $tokenBot, string $metodo, array $parametros = []): array
{
    $url = 'https://api.telegram.org/bot' . $tokenBot . '/' . $metodo;

    $curl = curl_init($url);
    if ($curl === false) {
        fwrite(STDERR, "No se pudo inicializar cURL.\n");
        exit(1);
    }

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $parametros,
    ]);

    $respuesta = curl_exec($curl);
    $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($respuesta === false) {
        fwrite(STDERR, "Fallo cURL llamando a Telegram: {$error}\n");
        exit(1);
    }

    $datos = json_decode($respuesta, true);
    if (!is_array($datos)) {
        fwrite(STDERR, "Telegram devolvio una respuesta no JSON. HTTP {$codigo}\n");
        exit(1);
    }

    if ($codigo < 200 || $codigo >= 300 || ($datos['ok'] ?? false) !== true) {
        fwrite(STDERR, "Telegram devolvio error HTTP {$codigo}:\n");
        fwrite(STDERR, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        exit(1);
    }

    return $datos;
}
