<?php

declare(strict_types=1);

use Fabularia\Controladores\ControladorLibros;
use Fabularia\Controladores\ControladorPrestamos;
use Fabularia\Controladores\ControladorCatalogoLibros;
use Fabularia\Controladores\ControladorTelegram;
use Fabularia\Controladores\ControladorUsuarios;
use Fabularia\Http\Enrutador;
use Fabularia\Http\RespuestaJson;
use Fabularia\Repositorios\RepositorioLibros;
use Fabularia\Repositorios\RepositorioPrestamos;
use Fabularia\Repositorios\RepositorioUsuarios;
use Fabularia\Servicios\ServicioCatalogoLibros;
use Fabularia\Servicios\ServicioCorreo;
use Fabularia\Servicios\ServicioWebhookPrestamos;

$contenedor = require __DIR__ . '/../config/bootstrap.php';
$pdo = $contenedor['pdo'];
$logger = $contenedor['logger'];

$repositorioUsuarios = new RepositorioUsuarios($pdo);
$repositorioLibros = new RepositorioLibros($pdo);
$repositorioPrestamos = new RepositorioPrestamos($pdo);

/**
 * Normaliza la ruta teniendo en cuenta que en Apache/XAMPP puede existir un prefijo
 * como /Fabularia/public. Sin esta normalización, el router no encontraría coincidencias.
 */
function normalizarRutaSolicitada(string $rutaSolicitada, string $rutaScript): string
{
    $rutaNormalizada = str_replace('\\', '/', $rutaSolicitada);
    $directorioScript = rtrim(str_replace('\\', '/', dirname($rutaScript)), '/');

    /*
     * Paso a paso:
     * 1) Detectamos el directorio base del script actual (por ejemplo /Fabularia/public).
     * 2) Si la ruta solicitada arranca por ese prefijo, lo eliminamos.
     * 3) Garantizamos que el valor final empieza por "/" y no queda vacío.
     */
    if (
        $directorioScript !== ''
        && $directorioScript !== '.'
        && $directorioScript !== '/'
        && str_starts_with($rutaNormalizada, $directorioScript)
    ) {
        $rutaNormalizada = substr($rutaNormalizada, strlen($directorioScript));
    }

    $rutaNormalizada = trim($rutaNormalizada);
    if ($rutaNormalizada === '' || $rutaNormalizada === '/index.php') {
        return '/';
    }

    return '/' . ltrim($rutaNormalizada, '/');
}

function obtenerBasePublica(string $rutaScript): string
{
    $basePublica = rtrim(str_replace('\\', '/', dirname($rutaScript)), '/');
    if ($basePublica === '' || $basePublica === '.') {
        return '';
    }

    return $basePublica;
}

function redirigir(string $basePublica, string $destinoRelativo): never
{
    header('Location: ' . $basePublica . $destinoRelativo);
    exit;
}

function obtenerUrlBaseAplicacion(string $basePublica): string
{
    $urlEntorno = trim((string) ($_ENV['APP_URL_BASE'] ?? ''));
    if ($urlEntorno !== '') {
        return rtrim($urlEntorno, '/');
    }

    $https = (string) ($_SERVER['HTTPS'] ?? '');
    $esHttps = $https !== '' && strtolower($https) !== 'off';
    $protocolo = $esHttps ? 'https' : 'http';
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));

    return rtrim($protocolo . '://' . $host . $basePublica, '/');
}

$metodoHttp = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$rutaBruta = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$ruta = normalizarRutaSolicitada($rutaBruta, $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$basePublica = obtenerBasePublica($_SERVER['SCRIPT_NAME'] ?? '/index.php');
$urlBaseApi = $basePublica . '/api';
$urlBaseAplicacion = obtenerUrlBaseAplicacion($basePublica);
$urlBaseTelegramBot = (string) ($_ENV['TELEGRAM_BOT_URL_BASE'] ?? 'https://t.me/Fabularia_bot?start=');
$autenticado = !empty($_SESSION['id_usuario']);

$servicioCatalogoLibros = new ServicioCatalogoLibros(
    $logger,
    (string) ($_ENV['GOOGLE_BOOKS_API_KEY'] ?? '')
);
$servicioWebhookPrestamos = new ServicioWebhookPrestamos(
    $logger,
    (string) ($_ENV['N8N_WEBHOOK_PRESTAMO'] ?? 'https://n8n.example/webhook-test/REDACTED')
);
$servicioCorreo = new ServicioCorreo(
    $logger,
    (string) ($_ENV['MAIL_FROM_EMAIL'] ?? ''),
    (string) ($_ENV['MAIL_FROM_NAME'] ?? 'Fabularia')
);

$controladorUsuarios = new ControladorUsuarios(
    $repositorioUsuarios,
    $servicioCorreo,
    $logger,
    $urlBaseAplicacion,
    (int) ($_ENV['PASSWORD_RESET_TTL_MINUTES'] ?? 30)
);
$controladorLibros = new ControladorLibros($repositorioLibros, $logger);
$controladorCatalogoLibros = new ControladorCatalogoLibros($servicioCatalogoLibros);
$controladorTelegram = new ControladorTelegram(
    $repositorioUsuarios,
    $logger,
    (string) ($_ENV['TELEGRAM_VINCULACION_TOKEN'] ?? '')
);
$controladorPrestamos = new ControladorPrestamos(
    $repositorioPrestamos,
    $repositorioLibros,
    $repositorioUsuarios,
    $servicioWebhookPrestamos,
    $logger
);

if ($metodoHttp === 'GET' && !str_starts_with($ruta, '/api/')) {
    if ($ruta === '/') {
        redirigir($basePublica, $autenticado ? '/app' : '/login');
    }

    if ($ruta === '/login') {
        if ($autenticado) {
            redirigir($basePublica, '/app');
        }
        require __DIR__ . '/vistas/login.php';
        exit;
    }

    if ($ruta === '/registro') {
        if ($autenticado) {
            redirigir($basePublica, '/app');
        }
        require __DIR__ . '/vistas/registro.php';
        exit;
    }

    if ($ruta === '/recuperar-contrasena') {
        if ($autenticado) {
            redirigir($basePublica, '/app');
        }
        require __DIR__ . '/vistas/recuperar_contrasena.php';
        exit;
    }

    if ($ruta === '/restablecer-contrasena') {
        if ($autenticado) {
            redirigir($basePublica, '/app');
        }
        require __DIR__ . '/vistas/restablecer_contrasena.php';
        exit;
    }

    if ($ruta === '/app') {
        if (!$autenticado) {
            redirigir($basePublica, '/login');
        }
        require __DIR__ . '/vistas/aplicacion.php';
        exit;
    }
}

$enrutador = new Enrutador();
$enrutador->registrar('GET', '/api/estado', static fn (): array => [200, ['estado' => 'ok', 'fecha' => date(DATE_ATOM)]]);

$enrutador->registrar('POST', '/api/usuarios/registro', static fn () => $controladorUsuarios->registrar());
$enrutador->registrar('POST', '/api/usuarios/login', static fn () => $controladorUsuarios->iniciarSesion());
$enrutador->registrar('POST', '/api/usuarios/logout', static fn () => $controladorUsuarios->cerrarSesion(), true);
$enrutador->registrar('POST', '/api/usuarios/solicitar-restablecimiento', static fn () => $controladorUsuarios->solicitarRestablecimientoContrasena());
$enrutador->registrar('POST', '/api/usuarios/restablecer-contrasena', static fn () => $controladorUsuarios->restablecerContrasenaConToken());
$enrutador->registrar('GET', '/api/usuarios/yo', static fn () => $controladorUsuarios->usuarioActual());
$enrutador->registrar('POST', '/api/usuarios/telefono', static fn () => $controladorUsuarios->actualizarTelefono(), true);
$enrutador->registrar('POST', '/api/usuarios/cambiar-contrasena', static fn () => $controladorUsuarios->cambiarContrasena(), true);
$enrutador->registrar('POST', '/api/usuarios/telegram/desvincular', static fn () => $controladorUsuarios->desvincularTelegram(), true);
$enrutador->registrar('DELETE', '/api/usuarios/cuenta', static fn () => $controladorUsuarios->eliminarCuenta(), true);
$enrutador->registrar('GET', '/api/catalogo/sugerencias', static fn () => $controladorCatalogoLibros->sugerencias(), true);
$enrutador->registrar('POST', '/api/telegram/vincular', static fn () => $controladorTelegram->vincularCuenta());

$enrutador->registrar('POST', '/api/libros', static fn () => $controladorLibros->publicarLibro(), true);
$enrutador->registrar('GET', '/api/libros', static fn () => $controladorLibros->listarDisponibles(), true);
$enrutador->registrar('GET', '/api/libros/mios', static fn () => $controladorLibros->listarMisLibros(), true);
$enrutador->registrar('DELETE', '/api/libros', static fn () => $controladorLibros->eliminarLibro(), true);

$enrutador->registrar('POST', '/api/prestamos', static fn () => $controladorPrestamos->solicitarPrestamo(), true);
$enrutador->registrar('GET', '/api/prestamos/mios', static fn () => $controladorPrestamos->listarMisPrestamos(), true);
$enrutador->registrar('POST', '/api/prestamos/devolver', static fn () => $controladorPrestamos->devolverPrestamo(), true);

try {
    [$codigo, $datos] = $enrutador->despachar($metodoHttp, $ruta, []);
    RespuestaJson::enviar($datos, $codigo);
} catch (Throwable $excepcion) {
    $logger->error('Error no controlado en la API', ['mensaje' => $excepcion->getMessage()]);
    RespuestaJson::enviar(
        ['error' => 'Se produjo un error interno. Revisa logs/app.log para más detalle.'],
        500
    );
}
