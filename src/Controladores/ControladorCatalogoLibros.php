<?php

declare(strict_types=1);

namespace Fabularia\Controladores;

use Fabularia\Servicios\ServicioCatalogoLibros;

final class ControladorCatalogoLibros
{
    public function __construct(private readonly ServicioCatalogoLibros $servicioCatalogoLibros)
    {
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function sugerencias(): array
    {
        $texto = trim((string) ($_GET['texto'] ?? ''));
        if (mb_strlen($texto) < 2) {
            return [200, ['sugerencias' => []]];
        }

        $sugerencias = $this->servicioCatalogoLibros->buscarSugerencias($texto, 8);
        return [200, ['sugerencias' => $sugerencias]];
    }
}
