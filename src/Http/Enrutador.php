<?php

declare(strict_types=1);

namespace Fabularia\Http;

final class Enrutador
{
    /**
     * @var array<int, array{metodo: string, ruta: string, manejador: callable, requiere_autenticacion: bool}>
     */
    private array $rutas = [];

    public function registrar(
        string $metodo,
        string $ruta,
        callable $manejador,
        bool $requiereAutenticacion = false
    ): void {
        $this->rutas[] = [
            'metodo' => strtoupper($metodo),
            'ruta' => $ruta,
            'manejador' => $manejador,
            'requiere_autenticacion' => $requiereAutenticacion,
        ];
    }

    /**
     * @param array<string, mixed> $contexto
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function despachar(string $metodo, string $ruta, array $contexto): array
    {
        foreach ($this->rutas as $rutaRegistrada) {
            if ($rutaRegistrada['metodo'] !== strtoupper($metodo)) {
                continue;
            }

            if ($rutaRegistrada['ruta'] !== $ruta) {
                continue;
            }

            if ($rutaRegistrada['requiere_autenticacion'] && empty($_SESSION['id_usuario'])) {
                return [401, ['error' => 'Debes iniciar sesión para usar este recurso.']];
            }

            $manejador = $rutaRegistrada['manejador'];
            return $manejador($contexto);
        }

        return [404, ['error' => 'Ruta no encontrada.']];
    }
}
