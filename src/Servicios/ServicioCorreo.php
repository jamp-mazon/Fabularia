<?php

declare(strict_types=1);

namespace Fabularia\Servicios;

use Monolog\Logger;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

final class ServicioCorreo
{
    private string $driver;
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUser;
    private string $smtpPass;
    private string $smtpEncryption;
    private bool $smtpAuth;
    private int $smtpTimeout;

    public function __construct(
        private readonly Logger $logger,
        private readonly string $remitenteEmail,
        private readonly string $remitenteNombre,
        array $config = []
    ) {
        $this->driver = mb_strtolower(trim((string) ($config['driver'] ?? 'mail')), 'UTF-8');
        $this->smtpHost = trim((string) ($config['smtp_host'] ?? ''));
        $this->smtpPort = max(1, (int) ($config['smtp_port'] ?? 587));
        $this->smtpUser = trim((string) ($config['smtp_user'] ?? ''));
        $this->smtpPass = (string) ($config['smtp_pass'] ?? '');
        $this->smtpEncryption = mb_strtolower(trim((string) ($config['smtp_encryption'] ?? 'tls')), 'UTF-8');
        $smtpAuthValor = $config['smtp_auth'] ?? true;
        $this->smtpAuth = !in_array(
            mb_strtolower(trim((string) $smtpAuthValor), 'UTF-8'),
            ['0', 'false', 'no', 'off'],
            true
        );
        $this->smtpTimeout = max(3, min(60, (int) ($config['smtp_timeout'] ?? 20)));
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
        $textoPlano = trim($textoPlano);
        if ($textoPlano === '') {
            $textoPlano = trim(strip_tags($html));
        }

        if ($this->debeUsarSmtp()) {
            return $this->enviarConSmtp(
                $destinatarioEmail,
                $asunto,
                $html,
                $textoPlano,
                $remitenteEmail,
                $nombre
            );
        }

        return $this->enviarConMailNativo(
            $destinatarioEmail,
            $asunto,
            $html,
            $textoPlano,
            $remitenteEmail,
            $nombre
        );
    }

    private function debeUsarSmtp(): bool
    {
        if ($this->driver === 'smtp') {
            return true;
        }

        return $this->smtpHost !== '' && $this->driver !== 'mail';
    }

    private function enviarConMailNativo(
        string $destinatarioEmail,
        string $asunto,
        string $html,
        string $textoPlano,
        string $remitenteEmail,
        string $nombre
    ): bool {
        $asuntoCodificado = mb_encode_mimeheader($asunto, 'UTF-8', 'B');
        $limiteMime = 'fabularia_' . bin2hex(random_bytes(8));
        $headers = [
            'MIME-Version: 1.0',
            'From: ' . $nombre . ' <' . $remitenteEmail . '>',
            'Reply-To: ' . $remitenteEmail,
            'Content-Type: multipart/alternative; boundary="' . $limiteMime . '"',
        ];

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

    private function enviarConSmtp(
        string $destinatarioEmail,
        string $asunto,
        string $html,
        string $textoPlano,
        string $remitenteEmail,
        string $nombre
    ): bool {
        if ($this->smtpHost === '') {
            $this->logger->warning('No se pudo enviar correo SMTP: SMTP_HOST vacio.', [
                'destinatario' => $destinatarioEmail,
                'asunto' => $asunto,
            ]);
            return false;
        }

        if ($this->smtpAuth && ($this->smtpUser === '' || $this->smtpPass === '')) {
            $this->logger->warning('No se pudo enviar correo SMTP: credenciales incompletas.', [
                'destinatario' => $destinatarioEmail,
                'asunto' => $asunto,
                'smtp_host' => $this->smtpHost,
                'smtp_port' => $this->smtpPort,
                'remitente' => $remitenteEmail,
            ]);
            return false;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->Port = $this->smtpPort;
            $mail->SMTPAuth = $this->smtpAuth;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->Timeout = $this->smtpTimeout;
            $mail->addReplyTo($remitenteEmail, $nombre);

            if (in_array($this->smtpEncryption, ['tls', 'ssl'], true)) {
                $mail->SMTPSecure = $this->smtpEncryption;
            }

            $mail->setFrom($remitenteEmail, $nombre);
            $mail->addAddress($destinatarioEmail);
            $mail->Subject = $asunto;
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = $textoPlano;

            return $mail->send();
        } catch (PHPMailerException $excepcion) {
            $this->logger->warning('Fallo al enviar correo SMTP', [
                'destinatario' => $destinatarioEmail,
                'asunto' => $asunto,
                'mensaje' => $excepcion->getMessage(),
                'error_info' => $mail->ErrorInfo,
                'smtp_host' => $this->smtpHost,
                'smtp_port' => $this->smtpPort,
                'smtp_encryption' => $this->smtpEncryption,
                'remitente' => $remitenteEmail,
            ]);
            return false;
        }
    }
}
