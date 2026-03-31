<?php

declare(strict_types=1);

use Fabularia\Controladores\ControladorLibros;
use Fabularia\Controladores\ControladorPrestamos;
use Fabularia\Controladores\ControladorUsuarios;
use Fabularia\Http\Enrutador;
use Fabularia\Http\RespuestaJson;
use Fabularia\Repositorios\RepositorioLibros;
use Fabularia\Repositorios\RepositorioPrestamos;
use Fabularia\Repositorios\RepositorioUsuarios;

$contenedor = require __DIR__ . '/../config/bootstrap.php';
$pdo = $contenedor['pdo'];
$logger = $contenedor['logger'];

$repositorioUsuarios = new RepositorioUsuarios($pdo);
$repositorioLibros = new RepositorioLibros($pdo);
$repositorioPrestamos = new RepositorioPrestamos($pdo);

$controladorUsuarios = new ControladorUsuarios($repositorioUsuarios, $logger);
$controladorLibros = new ControladorLibros($repositorioLibros, $logger);
$controladorPrestamos = new ControladorPrestamos($repositorioPrestamos, $repositorioLibros, $logger);

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

$metodoHttp = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$rutaBruta = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$ruta = normalizarRutaSolicitada($rutaBruta, $_SERVER['SCRIPT_NAME'] ?? '/index.php');

if ($metodoHttp === 'GET' && $ruta === '/') {
    require __DIR__ . '/vista_inicio.php';
    exit;
}

$enrutador = new Enrutador();
$enrutador->registrar('GET', '/api/estado', static fn (): array => [200, ['estado' => 'ok', 'fecha' => date(DATE_ATOM)]]);

$enrutador->registrar('POST', '/api/usuarios/registro', static fn () => $controladorUsuarios->registrar());
$enrutador->registrar('POST', '/api/usuarios/login', static fn () => $controladorUsuarios->iniciarSesion());
$enrutador->registrar('POST', '/api/usuarios/logout', static fn () => $controladorUsuarios->cerrarSesion(), true);
$enrutador->registrar('GET', '/api/usuarios/yo', static fn () => $controladorUsuarios->usuarioActual());

$enrutador->registrar('POST', '/api/libros', static fn () => $controladorLibros->publicarLibro(), true);
$enrutador->registrar('GET', '/api/libros', static fn () => $controladorLibros->listarDisponibles(), true);
$enrutador->registrar('GET', '/api/libros/mios', static fn () => $controladorLibros->listarMisLibros(), true);

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
