<?php

declare(strict_types=1);

$versionCss = (string) (@filemtime(__DIR__ . '/../assets/css/estilos.css') ?: time());
$versionJs = (string) (@filemtime(__DIR__ . '/../assets/js/comun.js') ?: time());
$urlCss = ($basePublica === '' ? '' : $basePublica) . '/assets/css/estilos.css?v=' . rawurlencode($versionCss);
$urlJs = ($basePublica === '' ? '' : $basePublica) . '/assets/js/comun.js?v=' . rawurlencode($versionJs);
$urlLogo = ($basePublica === '' ? '' : $basePublica) . '/assets/img/logo-fabularia-solo-crop-web.png';
$urlLogin = ($basePublica === '' ? '' : $basePublica) . '/login';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fabularia | Recuperar contrase&ntilde;a</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($urlCss, ENT_QUOTES) ?>">
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($urlLogo, ENT_QUOTES) ?>">
</head>
<body>
<main class="pagina pagina--auth">
    <section class="auth-layout">
        <article class="tarjeta info-card">
            <div class="marca-bloque marca-bloque--auth">
                <img class="marca-logo marca-logo--auth" src="<?= htmlspecialchars($urlLogo, ENT_QUOTES) ?>" alt="Logo de Fabularia">
                <h1 class="marca-titulo">Fabularia</h1>
            </div>
            <p>Introduce el email de tu cuenta y te enviaremos un enlace para restablecer tu contrase&ntilde;a.</p>
        </article>

        <article class="tarjeta auth-card">
            <h2>Recuperar contrase&ntilde;a</h2>
            <form id="formularioRecuperar">
                <label for="emailRecuperar">Email</label>
                <input id="emailRecuperar" name="email" type="email" required>

                <button type="submit" style="margin-top:1rem;">Enviar enlace</button>
            </form>

            <div id="mensaje" class="mensaje"></div>
            <p class="pequeno"><a href="<?= htmlspecialchars($urlLogin, ENT_QUOTES) ?>">Volver al login</a></p>
        </article>
    </section>
</main>

<script src="<?= htmlspecialchars($urlJs, ENT_QUOTES) ?>"></script>
<script>
    window.fabularia.configurar({
        urlBaseApi: <?= json_encode($urlBaseApi, JSON_UNESCAPED_SLASHES) ?>
    });

    const formulario = document.getElementById("formularioRecuperar");
    const mensaje = document.getElementById("mensaje");
    let temporizadorMensaje = null;

    function limpiarMensaje() {
        if (temporizadorMensaje !== null) {
            clearTimeout(temporizadorMensaje);
            temporizadorMensaje = null;
        }
        mensaje.className = "mensaje";
        mensaje.textContent = "";
    }

    function mostrarMensaje(texto, tipo = "ok", duracionMs = 5000) {
        limpiarMensaje();
        if (!texto) return;

        mensaje.className = `mensaje ${tipo}`;
        mensaje.textContent = texto;

        if (duracionMs > 0) {
            temporizadorMensaje = setTimeout(() => {
                limpiarMensaje();
            }, duracionMs);
        }
    }

    formulario.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarMensaje();

        try {
            await window.fabularia.llamarApi("/usuarios/solicitar-restablecimiento", "POST", {
                email: formulario.email.value
            });
            mostrarMensaje("Si el correo electr\u00F3nico existe, recibir\u00E1s instrucciones para restablecer la contrase\u00F1a.", "ok", 6000);
            formulario.reset();
        } catch (error) {
            mostrarMensaje(error.message, "error", 5000);
        }
    });
</script>
</body>
</html>
