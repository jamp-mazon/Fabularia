<?php

declare(strict_types=1);

namespace Fabularia\Http;

final class SolicitudHttp
{
    public static function obtenerDatosEntrada(): array
    {
        if (!empty($_POST)) {
            return $_POST;
        }

        $cuerpoCrudo = file_get_contents('php://input');
        if ($cuerpoCrudo === false || trim($cuerpoCrudo) === '') {
            return [];
        }

        /*
         * Este bloque permite aceptar JSON sin romper compatibilidad con formularios:
         * 1) Se lee el cuerpo crudo tal como llega por HTTP.
         * 2) Se intenta decodificar como JSON.
         * 3) Solo si el resultado es un array válido, se devuelve.
         * 4) En cualquier otro caso, se devuelve array vacío para que el controlador valide.
         */
        $datosJson = json_decode($cuerpoCrudo, true);
        return is_array($datosJson) ? $datosJson : [];
    }

    public static function obtenerTexto(array $datos, string $clave): string
    {
        $valor = $datos[$clave] ?? '';
        return is_string($valor) ? trim($valor) : '';
    }

    public static function obtenerEntero(array $datos, string $clave): int
    {
        $valor = $datos[$clave] ?? 0;
        return (int) $valor;
    }
}
