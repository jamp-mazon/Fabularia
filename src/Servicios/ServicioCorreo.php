<?php

declare(strict_types=1);

namespace Fabularia\Servicios;

use Monolog\Logger;

final class ServicioCorreo
{
    public function __construct(
        private readonly Logger $logger,
        private readonly string $remitenteEmail,
        private readonly string $remitenteNombre
    ) {
    }

    public function enviarCorreoHtml(
        string $destinatarioEmail,
        string $asunto,
        string $html,
        string $textoPlano = ''
    ): bool {
        $destinatarioEmail = trim($destinatarioEmail);
        if ($destinatarioEmail === '' || !filter_var($destinatarioEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $remitenteEmail = trim($this->remitenteEmail);
        if ($remitenteEmail === '' || !filter_var($remitenteEmail, FILTER_VALIDATE_EMAIL)) {
            $this->logger->warning('No se pudo enviar correo: remitente no valido.', [
                'destinatario' => $destinatarioEmail,
            ]);
            return false;
        }

        $nombre = trim($this->remitenteNombre) !== '' ? trim($this->remitenteNombre) : 'Fabularia';
        $asuntoCodificado = mb_encode_mimeheader($asunto, 'UTF-8', 'B');

        $limiteMime = 'fabularia_' . bin2hex(random_bytes(8));
        $headers = [
            'MIME-Version: 1.0',
            'From: ' . $nombre . ' <' . $remitenteEmail . '>',
            'Reply-To: ' . $remitenteEmail,
            'Content-Type: multipart/alternative; boundary="' . $limiteMime . '"',
        ];

        $textoPlano = trim($textoPlano);
        if ($textoPlano === '') {
            $textoPlano = trim(strip_tags($html));
        }

        $cuerpo = [];
        $cuerpo[] = '--' . $limiteMime;
        $cuerpo[] = 'Content-Type: text/plain; charset=UTF-8';
        $cuerpo[] = 'Content-Transfer-Encoding: 8bit';
        $cuerpo[] = '';
        $cuerpo[] = $textoPlano;
        $cuerpo[] = '';
        $cuerpo[] = '--' . $limiteMime;
        $cuerpo[] = 'Content-Type: text/html; charset=UTF-8';
        $cuerpo[] = 'Content-Transfer-Encoding: 8bit';
        $cuerpo[] = '';
        $cuerpo[] = $html;
        $cuerpo[] = '';
        $cuerpo[] = '--' . $limiteMime . '--';
        $cuerpoFinal = implode("\r\n", $cuerpo);

        $enviado = @mail($destinatarioEmail, $asuntoCodificado, $cuerpoFinal, implode("\r\n", $headers));
        if (!$enviado) {
            $this->logger->warning('Fallo al enviar correo con mail()', [
                'destinatario' => $destinatarioEmail,
                'asunto' => $asunto,
            ]);
        }

        return $enviado;
    }
}

