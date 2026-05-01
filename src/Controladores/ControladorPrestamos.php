<?php

declare(strict_types=1);

namespace Fabularia\Controladores;

use Fabularia\Http\SolicitudHttp;
use Fabularia\Repositorios\RepositorioLibros;
use Fabularia\Repositorios\RepositorioPrestamos;
use Fabularia\Repositorios\RepositorioUsuarios;
use Fabularia\Servicios\NormalizadorGeneroLibros;
use Fabularia\Servicios\ServicioLecturaPublica;
use Fabularia\Servicios\ServicioWebhookPrestamos;
use Monolog\Logger;
use RuntimeException;

final class ControladorPrestamos
{
    public function __construct(
        private readonly RepositorioPrestamos $repositorioPrestamos,
        private readonly RepositorioLibros $repositorioLibros,
        private readonly RepositorioUsuarios $repositorioUsuarios,
        private readonly ServicioLecturaPublica $servicioLecturaPublica,
        private readonly ServicioWebhookPrestamos $servicioWebhookPrestamos,
        private readonly Logger $logger
    ) {
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function solicitarPrestamo(): array
    {
        $idUsuarioPrestado = (int) ($_SESSION['id_usuario'] ?? 0);
        $datos = SolicitudHttp::obtenerDatosEntrada();
        $idLibro = SolicitudHttp::obtenerEntero($datos, 'id_libro');

        if ($idLibro <= 0) {
            return [422, ['error' => 'Debes indicar un id_libro válido.']];
        }

        $libro = $this->repositorioLibros->obtenerPorId($idLibro);
        if ($libro === null) {
            return [404, ['error' => 'El libro solicitado no existe.']];
        }

        $idUsuarioDueno = (int) $libro['id_usuario'];
        if ($idUsuarioDueno === $idUsuarioPrestado) {
            return [400, ['error' => 'No puedes pedir prestado tu propio libro.']];
        }

        if (!$this->repositorioLibros->usuarioTieneLibroDisponibleParaIntercambio($idUsuarioPrestado)) {
            return [409, ['error' => 'Para solicitar un préstamo debes tener al menos un libro propio disponible para intercambio.']];
        }

        if ((int) $libro['activo_intercambio'] !== 1) {
            return [409, ['error' => 'Este libro ya no está disponible para intercambio.']];
        }

        if ($this->repositorioPrestamos->existePrestamoActivo($idLibro)) {
            return [409, ['error' => 'El libro ya está prestado actualmente.']];
        }

        [$fechaLimiteDevolucion, $errorFecha] = $this->resolverFechaLimiteDevolucion($datos);
        if ($errorFecha !== null) {
            return [422, ['error' => $errorFecha]];
        }

        $referenciaLectura = $this->servicioLecturaPublica->construirReferenciaLecturaParaLibro($libro);

        $idPrestamo = $this->repositorioPrestamos->crearPrestamo(
            $idLibro,
            $idUsuarioDueno,
            $idUsuarioPrestado,
            $referenciaLectura,
            $fechaLimiteDevolucion
        );

        $this->logger->info('Préstamo creado', [
            'id_prestamo' => $idPrestamo,
            'id_libro' => $idLibro,
            'id_usuario_dueno' => $idUsuarioDueno,
            'id_usuario_prestado' => $idUsuarioPrestado,
            'fecha_limite_devolucion' => $fechaLimiteDevolucion,
            'lectura_publica' => (int) ($referenciaLectura['lectura_publica'] ?? 0),
            'lectura_fuente' => $referenciaLectura['lectura_fuente'] ?? null,
        ]);

        $this->enviarNotificacionWebhook(
            $idPrestamo,
            $libro,
            $idUsuarioDueno,
            $idUsuarioPrestado,
            $fechaLimiteDevolucion
        );

        return [201, ['mensaje' => 'Préstamo solicitado correctamente.', 'id_prestamo' => $idPrestamo]];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function listarMisPrestamos(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        $prestamos = $this->repositorioPrestamos->listarPrestamosDeUsuario($idUsuario);
        foreach ($prestamos as &$prestamo) {
            $prestamo['genero'] = NormalizadorGeneroLibros::normalizarParaGuardar((string) ($prestamo['genero'] ?? ''));
        }
        unset($prestamo);

        return [200, ['prestamos' => $prestamos]];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function leerPrestamo(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        $idPrestamo = (int) ($_GET['id_prestamo'] ?? 0);
        $paginaSolicitada = (int) ($_GET['pagina'] ?? 1);

        if ($idPrestamo <= 0) {
            return [422, ['error' => 'Debes indicar un id_prestamo válido.']];
        }

        $prestamo = $this->repositorioPrestamos->obtenerPrestamoDeUsuario($idPrestamo, $idUsuario);
        if ($prestamo === null) {
            return [404, ['error' => 'No se encontró el préstamo solicitado.']];
        }

        if ($prestamo['fecha_devolucion'] !== null) {
            return [409, ['error' => 'El préstamo ya fue devuelto y no admite lectura.']];
        }

        if ((int) ($prestamo['lectura_publica'] ?? 0) !== 1) {
            $referenciaDemo = $this->servicioLecturaPublica->construirReferenciaLecturaParaLibro($prestamo);
            $this->repositorioPrestamos->actualizarFuenteLectura($idPrestamo, $idUsuario, $referenciaDemo);
            $prestamoActualizado = $this->repositorioPrestamos->obtenerPrestamoDeUsuario($idPrestamo, $idUsuario);
            if ($prestamoActualizado !== null) {
                $prestamo = $prestamoActualizado;
            }
        }

        try {
            $paginaSolicitada = max(1, $paginaSolicitada);
            $lectura = $this->servicioLecturaPublica->obtenerPaginaLectura($prestamo, $paginaSolicitada);

            $this->repositorioPrestamos->actualizarProgresoLectura(
                $idPrestamo,
                $idUsuario,
                (int) $lectura['pagina_actual'],
                (int) $lectura['total_paginas']
            );

            return [
                200,
                [
                    'lectura' => [
                        'id_prestamo' => $idPrestamo,
                        'titulo' => (string) ($prestamo['titulo'] ?? ''),
                        'autor' => (string) ($prestamo['autor'] ?? ''),
                        'dueno' => (string) ($prestamo['nombre_dueno'] ?? ''),
                        'pagina_actual' => (int) $lectura['pagina_actual'],
                        'total_paginas' => (int) $lectura['total_paginas'],
                        'porcentaje_progreso' => (float) $lectura['porcentaje_progreso'],
                        'contenido' => (string) $lectura['contenido'],
                    ],
                ],
            ];
        } catch (RuntimeException $excepcion) {
            $this->logger->warning('Fallo de lectura de préstamo', [
                'id_prestamo' => $idPrestamo,
                'id_usuario' => $idUsuario,
                'mensaje' => $excepcion->getMessage(),
                'lectura_fuente' => (string) ($prestamo['lectura_fuente'] ?? ''),
                'lectura_formato' => (string) ($prestamo['lectura_formato'] ?? ''),
                'lectura_url' => (string) ($prestamo['lectura_url'] ?? ''),
            ]);

            $mensaje = $excepcion->getMessage();
            if (str_contains(mb_strtolower($mensaje, 'UTF-8'), 'no esta en espanol')) {
                $referenciaNueva = $this->servicioLecturaPublica->buscarReferenciaPublica(
                    (string) ($prestamo['titulo'] ?? ''),
                    (string) ($prestamo['autor'] ?? '')
                );
                $tieneNuevaFuente = (int) ($referenciaNueva['lectura_publica'] ?? 0) === 1
                    && trim((string) ($referenciaNueva['lectura_url'] ?? '')) !== '';
                $urlActual = trim((string) ($prestamo['lectura_url'] ?? ''));
                $urlNueva = trim((string) ($referenciaNueva['lectura_url'] ?? ''));

                if ($tieneNuevaFuente && $urlNueva !== '' && $urlNueva !== $urlActual) {
                    $this->repositorioPrestamos->actualizarFuenteLectura(
                        $idPrestamo,
                        $idUsuario,
                        $referenciaNueva
                    );
                    $prestamoActualizado = $this->repositorioPrestamos->obtenerPrestamoDeUsuario($idPrestamo, $idUsuario);
                    if ($prestamoActualizado !== null) {
                        try {
                            $lecturaReintentada = $this->servicioLecturaPublica->obtenerPaginaLectura($prestamoActualizado, $paginaSolicitada);
                            $this->repositorioPrestamos->actualizarProgresoLectura(
                                $idPrestamo,
                                $idUsuario,
                                (int) $lecturaReintentada['pagina_actual'],
                                (int) $lecturaReintentada['total_paginas']
                            );

                            return [
                                200,
                                [
                                    'lectura' => [
                                        'id_prestamo' => $idPrestamo,
                                        'titulo' => (string) ($prestamoActualizado['titulo'] ?? ''),
                                        'autor' => (string) ($prestamoActualizado['autor'] ?? ''),
                                        'dueno' => (string) ($prestamoActualizado['nombre_dueno'] ?? ''),
                                        'pagina_actual' => (int) $lecturaReintentada['pagina_actual'],
                                        'total_paginas' => (int) $lecturaReintentada['total_paginas'],
                                        'porcentaje_progreso' => (float) $lecturaReintentada['porcentaje_progreso'],
                                        'contenido' => (string) $lecturaReintentada['contenido'],
                                    ],
                                ],
                            ];
                        } catch (\Throwable) {
                            // Si falla el reintento, cae en el return de error original.
                        }
                    }
                }

                // Si no hay reemplazo en español, invalidamos la fuente para no reutilizar
                // una referencia incorrecta en intentos siguientes.
                $this->repositorioPrestamos->actualizarFuenteLectura($idPrestamo, $idUsuario, [
                    'lectura_publica' => 0,
                    'lectura_fuente' => null,
                    'lectura_id_externo' => null,
                    'lectura_url' => null,
                    'lectura_formato' => null,
                ]);
            }

            return [409, ['error' => $this->traducirErrorLectura($excepcion->getMessage())]];
        } catch (\Throwable $excepcion) {
            $this->logger->error('Error al leer préstamo', [
                'id_prestamo' => $idPrestamo,
                'id_usuario' => $idUsuario,
                'mensaje' => $excepcion->getMessage(),
            ]);

            return [500, ['error' => 'No se pudo abrir el lector en este momento.']];
        }
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function guardarProgresoLectura(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        $datos = SolicitudHttp::obtenerDatosEntrada();
        $idPrestamo = SolicitudHttp::obtenerEntero($datos, 'id_prestamo');
        $paginaActual = SolicitudHttp::obtenerEntero($datos, 'pagina_actual');
        $paginasTotales = SolicitudHttp::obtenerEntero($datos, 'total_paginas');

        if ($idPrestamo <= 0 || $paginaActual <= 0) {
            return [422, ['error' => 'Debes indicar id_prestamo y pagina_actual válidos.']];
        }

        $prestamo = $this->repositorioPrestamos->obtenerPrestamoDeUsuario($idPrestamo, $idUsuario);
        if ($prestamo === null) {
            return [404, ['error' => 'No se encontró el préstamo seleccionado.']];
        }

        if ($prestamo['fecha_devolucion'] !== null) {
            return [409, ['error' => 'Este préstamo ya fue devuelto.']];
        }

        if ((int) ($prestamo['lectura_publica'] ?? 0) !== 1) {
            $referenciaDemo = $this->servicioLecturaPublica->construirReferenciaLecturaParaLibro($prestamo);
            $this->repositorioPrestamos->actualizarFuenteLectura($idPrestamo, $idUsuario, $referenciaDemo);
            $prestamoActualizado = $this->repositorioPrestamos->obtenerPrestamoDeUsuario($idPrestamo, $idUsuario);
            if ($prestamoActualizado !== null) {
                $prestamo = $prestamoActualizado;
            } else {
                return [409, ['error' => 'Este libro no dispone de lectura pública.']];
            }
        }

        if ($paginasTotales <= 0) {
            $paginasTotales = (int) ($prestamo['paginas_lectura_totales'] ?? 0);
        }

        $paginasTotales = max(1, $paginasTotales);
        $paginaActual = min(max(1, $paginaActual), $paginasTotales);

        $actualizado = $this->repositorioPrestamos->actualizarProgresoLectura(
            $idPrestamo,
            $idUsuario,
            $paginaActual,
            $paginasTotales
        );

        if (!$actualizado) {
            return [404, ['error' => 'No se pudo guardar el progreso de lectura.']];
        }

        $porcentaje = (float) round(($paginaActual / $paginasTotales) * 100, 2);

        return [
            200,
            [
                'mensaje' => 'Progreso de lectura guardado.',
                'pagina_actual' => $paginaActual,
                'total_paginas' => $paginasTotales,
                'porcentaje_progreso' => $porcentaje,
            ],
        ];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function devolverPrestamo(): array
    {
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
        $datos = SolicitudHttp::obtenerDatosEntrada();
        $idPrestamo = SolicitudHttp::obtenerEntero($datos, 'id_prestamo');

        if ($idPrestamo <= 0) {
            return [422, ['error' => 'Debes indicar un id_prestamo válido.']];
        }

        $actualizado = $this->repositorioPrestamos->devolverPrestamo($idPrestamo, $idUsuario);
        if (!$actualizado) {
            return [404, ['error' => 'No se encontró un préstamo activo para devolver con ese id.']];
        }

        $this->logger->info('Préstamo devuelto', ['id_prestamo' => $idPrestamo, 'id_usuario' => $idUsuario]);
        return [200, ['mensaje' => 'Préstamo devuelto correctamente.']];
    }

    /**
     * @param array<string, mixed> $libro
     */
    private function enviarNotificacionWebhook(
        int $idPrestamo,
        array $libro,
        int $idUsuarioDueno,
        int $idUsuarioPrestado,
        ?string $fechaLimiteDevolucion
    ): void {
        $usuarioDueno = $this->repositorioUsuarios->obtenerPorId($idUsuarioDueno);
        $usuarioPrestado = $this->repositorioUsuarios->obtenerPorId($idUsuarioPrestado);

        if ($usuarioDueno === null || $usuarioPrestado === null) {
            $this->logger->warning('No se pudo construir payload n8n por usuarios inexistentes', [
                'id_prestamo' => $idPrestamo,
            ]);
            return;
        }

        $payload = [
            'evento' => 'prestamo_creado',
            'fecha_evento' => date(DATE_ATOM),
            'prestamo' => [
                'id' => $idPrestamo,
                'fecha_limite_devolucion' => $fechaLimiteDevolucion,
            ],
            'libro' => [
                'id' => (int) ($libro['id'] ?? 0),
                'titulo' => (string) ($libro['titulo'] ?? ''),
                'portada_url' => $libro['portada_url'] ?? null,
            ],
            'usuario_dueno' => [
                'id' => (int) $usuarioDueno['id'],
                'nombre' => trim((string) $usuarioDueno['nombre'] . ' ' . (string) $usuarioDueno['apellidos']),
                'email' => (string) $usuarioDueno['email'],
                'telefono' => $usuarioDueno['telefono'],
                'telegram_chat_id' => $usuarioDueno['telegram_chat_id'],
                'telegram_usuario' => $usuarioDueno['telegram_usuario'],
            ],
            'usuario_receptor' => [
                'id' => (int) $usuarioPrestado['id'],
                'nombre' => trim((string) $usuarioPrestado['nombre'] . ' ' . (string) $usuarioPrestado['apellidos']),
                'email' => (string) $usuarioPrestado['email'],
                'telefono' => $usuarioPrestado['telefono'],
                'telegram_chat_id' => $usuarioPrestado['telegram_chat_id'],
                'telegram_usuario' => $usuarioPrestado['telegram_usuario'],
            ],
        ];

        $this->servicioWebhookPrestamos->notificarPrestamoCreado($payload);
    }

    private function traducirErrorLectura(string $mensaje): string
    {
        $mensajeNormalizado = mb_strtolower(trim($mensaje), 'UTF-8');

        if (str_contains($mensajeNormalizado, 'no esta en espanol')) {
            return 'No se encontró una versión pública en español para este libro.';
        }

        if (str_contains($mensajeNormalizado, 'no tiene lectura publica disponible')) {
            return 'Este libro no tiene lectura pública disponible.';
        }

        if (
            str_contains($mensajeNormalizado, 'archivo del libro')
            || str_contains($mensajeNormalizado, 'epub')
            || str_contains($mensajeNormalizado, 'pdf')
            || str_contains($mensajeNormalizado, 'ziparchive')
        ) {
            return 'No se pudo abrir el archivo de lectura de este libro. Revisa el formato EPUB/PDF y vuelve a intentarlo.';
        }

        if (
            str_contains($mensajeNormalizado, 'fallo de red')
            || str_contains($mensajeNormalizado, 'timeout')
            || str_contains($mensajeNormalizado, 'http')
        ) {
            return 'No se pudo cargar el texto público ahora. Inténtalo de nuevo en unos segundos.';
        }

        return $mensaje;
    }

    /**
     * @param array<string, mixed> $datos
     * @return array{0: string|null, 1: string|null}
     */
    private function resolverFechaLimiteDevolucion(array $datos): array
    {
        $entrada = trim((string) ($datos['fecha_limite_devolucion'] ?? ''));

        $hoy = new \DateTimeImmutable('today');
        $maximo = $hoy->modify('+14 days');

        if ($entrada === '') {
            $predeterminada = $maximo->setTime(23, 59, 59);
            return [$predeterminada->format('Y-m-d H:i:s'), null];
        }

        $fecha = \DateTimeImmutable::createFromFormat('Y-m-d', $entrada);
        if (!$fecha) {
            return [null, 'La fecha límite de devolución no tiene un formato válido.'];
        }

        $fecha = $fecha->setTime(23, 59, 59);
        if ($fecha < $hoy->setTime(0, 0, 0)) {
            return [null, 'La fecha límite no puede ser anterior a hoy.'];
        }

        if ($fecha > $maximo->setTime(23, 59, 59)) {
            return [null, 'La fecha límite no puede superar 14 días desde hoy.'];
        }

        return [$fecha->format('Y-m-d H:i:s'), null];
    }
}
