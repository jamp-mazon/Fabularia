<?php

declare(strict_types=1);

namespace Fabularia\Controladores;

use Fabularia\Http\SolicitudHttp;
use Fabularia\Repositorios\RepositorioLibros;
use Monolog\Logger;

final class ControladorLibros
{
    public function __construct(
        private readonly RepositorioLibros $repositorioLibros,
        private readonly Logger $logger
    ) {
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function publicarLibro(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        $datos = SolicitudHttp::obtenerDatosEntrada();

        $titulo = SolicitudHttp::obtenerTexto($datos, 'titulo');
        $autor = SolicitudHttp::obtenerTexto($datos, 'autor');
        $genero = SolicitudHttp::obtenerTexto($datos, 'genero');
        $descripcion = SolicitudHttp::obtenerTexto($datos, 'descripcion');
        $descripcion = $descripcion === '' ? null : $descripcion;

        if ($titulo === '' || $autor === '' || $genero === '') {
            return [422, ['error' => 'Título, autor y género son obligatorios.']];
        }

        $idLibro = $this->repositorioLibros->crearLibro($idUsuario, $titulo, $autor, $genero, $descripcion);
        $this->logger->info('Libro publicado para intercambio', ['id_libro' => $idLibro, 'id_usuario' => $idUsuario]);

        return [
            201,
            [
                'mensaje' => 'Libro publicado correctamente.',
                'libro' => [
                    'id' => $idLibro,
                    'titulo' => $titulo,
                    'autor' => $autor,
                    'genero' => $genero,
                ],
            ],
        ];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function listarDisponibles(): array
    {
        $terminoBusqueda = trim((string) ($_GET['buscar'] ?? ''));
        $genero = trim((string) ($_GET['genero'] ?? ''));
        $idUsuarioActual = (int) ($_SESSION['id_usuario'] ?? 0);
        $idUsuarioActual = $idUsuarioActual > 0 ? $idUsuarioActual : null;

        $libros = $this->repositorioLibros->listarDisponibles($terminoBusqueda, $genero, $idUsuarioActual);
        return [200, ['libros' => $libros]];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function listarMisLibros(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        $libros = $this->repositorioLibros->listarPorUsuario($idUsuario);
        return [200, ['libros' => $libros]];
    }
}
