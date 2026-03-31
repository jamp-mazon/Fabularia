<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Fabularia\Infraestructura\ConexionBD;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$raizProyecto = dirname(__DIR__);
if (file_exists($raizProyecto . '/.env')) {
    Dotenv::createImmutable($raizProyecto)->safeLoad();
}

$zonaHoraria = $_ENV['APP_TIMEZONE'] ?? 'Europe/Madrid';
date_default_timezone_set($zonaHoraria);

$rutaLog = $raizProyecto . '/logs/app.log';
if (!is_dir(dirname($rutaLog))) {
    mkdir(dirname($rutaLog), 0777, true);
}

$logger = new Logger('fabularia');
$logger->pushHandler(new StreamHandler($rutaLog, Level::Info));

$pdo = ConexionBD::crearDesdeEntorno();

return [
    'pdo' => $pdo,
    'logger' => $logger,
];
