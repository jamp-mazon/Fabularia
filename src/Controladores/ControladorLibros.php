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
        $portadaUrl = SolicitudHttp::obtenerTexto($datos, 'portada_url');
        $portadaUrl = $portadaUrl === '' ? null : $portadaUrl;
        $descripcion = SolicitudHttp::obtenerTexto($datos, 'descripcion');
        $descripcion = $descripcion === '' ? null : $descripcion;

        if ($titulo === '' || $autor === '' || $genero === '') {
            return [422, ['error' => 'Titulo, autor y genero son obligatorios.']];
        }

        if ($portadaUrl !== null && filter_var($portadaUrl, FILTER_VALIDATE_URL) === false) {
            return [422, ['error' => 'La portada debe ser una URL valida (http o https).']];
        }

        $idLibro = $this->repositorioLibros->crearLibro(
            $idUsuario,
            $titulo,
            $autor,
            $genero,
            $portadaUrl,
            $descripcion
        );
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
                    'portada_url' => $portadaUrl,
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

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function eliminarLibro(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        $datos = SolicitudHttp::obtenerDatosEntrada();
        $idLibro = SolicitudHttp::obtenerEntero($datos, 'id_libro');

        if ($idLibro <= 0) {
            return [422, ['error' => 'Debes indicar un id_libro valido para eliminar.']];
        }

        $libro = $this->repositorioLibros->obtenerPorId($idLibro);
        if ($libro === null) {
            return [404, ['error' => 'No existe el libro solicitado.']];
        }

        if ((int) $libro['id_usuario'] !== $idUsuario) {
            return [403, ['error' => 'No puedes eliminar libros de otro usuario.']];
        }

        if ($this->repositorioLibros->existePrestamoActivoPorLibro($idLibro)) {
            return [
                409,
                ['error' => 'No puedes eliminar este libro mientras tenga un prestamo activo. Espera a la devolucion.'],
            ];
        }

        $eliminado = $this->repositorioLibros->eliminarLibroDeUsuario($idLibro, $idUsuario);
        if (!$eliminado) {
            return [404, ['error' => 'No se pudo eliminar el libro indicado.']];
        }

        $this->logger->info('Libro eliminado por el propietario', [
            'id_libro' => $idLibro,
            'id_usuario' => $idUsuario,
        ]);

        return [200, ['mensaje' => 'Libro eliminado correctamente.']];
    }
}
