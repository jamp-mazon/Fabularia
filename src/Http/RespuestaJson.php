<?php

declare(strict_types=1);

namespace Fabularia\Http;

final class RespuestaJson
{
    public static function enviar(array $datos, int $codigoEstado = 200): void
    {
        http_response_code($codigoEstado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
