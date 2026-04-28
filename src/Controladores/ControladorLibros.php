<?php

declare(strict_types=1);

namespace Fabularia\Controladores;

use Fabularia\Http\SolicitudHttp;
use Fabularia\Repositorios\RepositorioLibros;
use Fabularia\Servicios\NormalizadorGeneroLibros;
use Monolog\Logger;

final class ControladorLibros
{
    private const MAX_BYTES_ARCHIVO_LIBRO = 25_000_000;

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
        $genero = NormalizadorGeneroLibros::normalizarParaGuardar($genero);
        $portadaUrl = SolicitudHttp::obtenerTexto($datos, 'portada_url');
        $portadaUrl = $portadaUrl === '' ? null : $portadaUrl;
        $descripcion = SolicitudHttp::obtenerTexto($datos, 'descripcion');
        $descripcion = $descripcion === '' ? null : $descripcion;
        $archivoLibro = $this->procesarArchivoLibro($idUsuario);

        if (($archivoLibro['error'] ?? null) !== null) {
            return [422, ['error' => (string) $archivoLibro['error']]];
        }

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
            $descripcion,
            $archivoLibro['ruta'] ?? null,
            $archivoLibro['mime'] ?? null,
            $archivoLibro['nombre_original'] ?? null
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
                    'archivo_subido' => ($archivoLibro['ruta'] ?? null) !== null,
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
        foreach ($libros as &$libro) {
            $libro['genero'] = NormalizadorGeneroLibros::normalizarParaGuardar((string) ($libro['genero'] ?? ''));
        }
        unset($libro);
        return [200, ['libros' => $libros]];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function listarMisLibros(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        $libros = $this->repositorioLibros->listarPorUsuario($idUsuario);
        foreach ($libros as &$libro) {
            $libro['genero'] = NormalizadorGeneroLibros::normalizarParaGuardar((string) ($libro['genero'] ?? ''));
        }
        unset($libro);
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

    /**
     * @return array{ruta?: string|null, mime?: string|null, nombre_original?: string|null, error?: string|null}
     */
    private function procesarArchivoLibro(int $idUsuario): array
    {
        $archivo = $_FILES['archivo_libro'] ?? null;
        if (!is_array($archivo) || !isset($archivo['error'])) {
            return ['ruta' => null, 'mime' => null, 'nombre_original' => null];
        }

        $error = (int) $archivo['error'];
        if ($error === UPLOAD_ERR_NO_FILE) {
            return ['ruta' => null, 'mime' => null, 'nombre_original' => null];
        }

        if (!$this->repositorioLibros->admiteArchivosLibros()) {
            return ['error' => 'Debes aplicar la migracion database/migracion_archivos_libros.sql antes de subir EPUB/PDF.'];
        }

        if ($error !== UPLOAD_ERR_OK) {
            return ['error' => 'No se pudo subir el archivo del libro.'];
        }

        $tmp = (string) ($archivo['tmp_name'] ?? '');
        $nombreOriginal = trim((string) ($archivo['name'] ?? 'libro'));
        $tamano = (int) ($archivo['size'] ?? 0);

        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['error' => 'Archivo subido invalido.'];
        }

        if ($tamano <= 0 || $tamano > self::MAX_BYTES_ARCHIVO_LIBRO) {
            return ['error' => 'El archivo debe pesar entre 1 byte y 25 MB.'];
        }

        $extension = strtolower((string) pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $permitidas = ['epub', 'pdf'];
        if (!in_array($extension, $permitidas, true)) {
            return ['error' => 'Solo se permiten archivos EPUB o PDF.'];
        }

        $mimeDetectado = (string) (mime_content_type($tmp) ?: '');
        $mimesPermitidos = [
            'epub' => ['application/epub+zip', 'application/zip'],
            'pdf' => ['application/pdf'],
        ];

        if ($mimeDetectado !== '' && !in_array($mimeDetectado, $mimesPermitidos[$extension], true)) {
            return ['error' => 'El tipo de archivo no coincide con un EPUB/PDF valido.'];
        }

        $directorioBase = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'libros';
        $subdirectorioUsuario = $directorioBase . DIRECTORY_SEPARATOR . 'usuario_' . max(1, $idUsuario);
        if (!is_dir($subdirectorioUsuario) && !mkdir($subdirectorioUsuario, 0777, true) && !is_dir($subdirectorioUsuario)) {
            return ['error' => 'No se pudo preparar el directorio de subida.'];
        }

        $nombreSeguro = bin2hex(random_bytes(16)) . '.' . $extension;
        $rutaDestino = $subdirectorioUsuario . DIRECTORY_SEPARATOR . $nombreSeguro;

        if (!move_uploaded_file($tmp, $rutaDestino)) {
            return ['error' => 'No se pudo guardar el archivo subido.'];
        }

        $rutaRelativa = 'storage/libros/usuario_' . max(1, $idUsuario) . '/' . $nombreSeguro;

        return [
            'ruta' => $rutaRelativa,
            'mime' => $mimeDetectado !== '' ? $mimeDetectado : null,
            'nombre_original' => $nombreOriginal !== '' ? $nombreOriginal : null,
        ];
    }
}
