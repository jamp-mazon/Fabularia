<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Fabularia\Servicios\ServicioCorreo;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

require __DIR__ . '/../vendor/autoload.php';

$raizProyecto = dirname(__DIR__);
if (is_file($raizProyecto . '/.env')) {
    Dotenv::createImmutable($raizProyecto)->safeLoad();
}

$destinatario = trim((string) ($argv[1] ?? ''));
if ($destinatario === '' || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php scripts/probar_correo.php correo@destino.com\n");
    exit(2);
}

$rutaLog = $raizProyecto . '/logs/app.log';
if (!is_dir(dirname($rutaLog))) {
    mkdir(dirname($rutaLog), 0777, true);
}

$logger = new Logger('fabularia');
$logger->pushHandler(new StreamHandler($rutaLog, Level::Info));

$servicioCorreo = new ServicioCorreo(
    $logger,
    (string) ($_ENV['MAIL_FROM_EMAIL'] ?? ''),
    (string) ($_ENV['MAIL_FROM_NAME'] ?? 'Fabularia'),
    [
        'driver' => (string) ($_ENV['MAIL_DRIVER'] ?? 'mail'),
        'smtp_host' => (string) ($_ENV['SMTP_HOST'] ?? ''),
        'smtp_port' => (int) ($_ENV['SMTP_PORT'] ?? 587),
        'smtp_user' => (string) ($_ENV['SMTP_USER'] ?? ''),
        'smtp_pass' => (string) ($_ENV['SMTP_PASS'] ?? ''),
        'smtp_encryption' => (string) ($_ENV['SMTP_ENCRYPTION'] ?? 'tls'),
        'smtp_auth' => (string) ($_ENV['SMTP_AUTH'] ?? 'true'),
        'smtp_timeout' => (int) ($_ENV['SMTP_TIMEOUT'] ?? 20),
    ]
);

echo "Probando correo Fabularia\n";
echo "Driver: " . (string) ($_ENV['MAIL_DRIVER'] ?? 'mail') . "\n";
echo "SMTP: " . (string) ($_ENV['SMTP_HOST'] ?? '') . ':' . (string) ($_ENV['SMTP_PORT'] ?? '587') . "\n";
echo "Cifrado: " . (string) ($_ENV['SMTP_ENCRYPTION'] ?? 'tls') . "\n";
echo "Autenticacion: " . (string) ($_ENV['SMTP_AUTH'] ?? 'true') . "\n";
echo "Usuario SMTP configurado: " . (trim((string) ($_ENV['SMTP_USER'] ?? '')) !== '' ? 'si' : 'no') . "\n";
echo "Password SMTP configurada: " . (trim((string) ($_ENV['SMTP_PASS'] ?? '')) !== '' ? 'si' : 'no') . "\n";
echo "Remitente: " . (string) ($_ENV['MAIL_FROM_EMAIL'] ?? '') . "\n";
echo "Destinatario: {$destinatario}\n";

$enviado = $servicioCorreo->enviarCorreoHtml(
    $destinatario,
    'Fabularia - prueba SMTP',
    '<p>Correo de prueba enviado desde Fabularia.</p>',
    'Correo de prueba enviado desde Fabularia.'
);

if ($enviado) {
    echo "OK: PHPMailer acepto el envio. Revisa bandeja de entrada y spam.\n";
    exit(0);
}

echo "ERROR: no se pudo enviar. Revisa logs/app.log para ver el motivo exacto.\n";
exit(1);
