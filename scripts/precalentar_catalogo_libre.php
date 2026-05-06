<?php

declare(strict_types=1);

use Fabularia\Servicios\ServicioLecturaPublica;

require __DIR__ . '/../vendor/autoload.php';

$contenedor = require __DIR__ . '/../config/bootstrap.php';
$logger = $contenedor['logger'];

$directorioCache = (string) ($_ENV['LECTURA_CACHE_DIR'] ?? (dirname(__DIR__) . '/storage/lecturas'));
$servicioLectura = new ServicioLecturaPublica($logger, $directorioCache);

$paginas = max(1, min(5, (int) ($argv[1] ?? 3)));
$porPagina = max(1, min(20, (int) ($argv[2] ?? 10)));
$idiomas = ['es', 'en'];

echo "Precalentando catalogo libre en {$directorioCache}\n";

foreach ($idiomas as $idioma) {
    for ($pagina = 1; $pagina <= $paginas; $pagina++) {
        $inicio = microtime(true);
        $resultado = $servicioLectura->buscarLibrosCatalogoLibrePaginadoPorIdioma(
            '',
            $idioma,
            $pagina,
            $porPagina,
            ''
        );

        $total = count($resultado['libros'] ?? []);
        $ms = (int) round((microtime(true) - $inicio) * 1000);
        echo "OK {$idioma} pagina {$pagina}: {$total} libro(s) en {$ms} ms\n";
    }
}

echo "Cache de catalogo libre precalentada.\n";
