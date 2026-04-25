<?php

declare(strict_types=1);

$versionCss = (string) (@filemtime(__DIR__ . '/../assets/css/estilos.css') ?: time());
$versionJs = (string) (@filemtime(__DIR__ . '/../assets/js/comun.js') ?: time());
$urlCss = ($basePublica === '' ? '' : $basePublica) . '/assets/css/estilos.css?v=' . rawurlencode($versionCss);
$urlJs = ($basePublica === '' ? '' : $basePublica) . '/assets/js/comun.js?v=' . rawurlencode($versionJs);
$urlLogo = ($basePublica === '' ? '' : $basePublica) . '/assets/img/logo-fabularia-solo-crop-web.png';
$urlRegistro = ($basePublica === '' ? '' : $basePublica) . '/registro';
$urlRecuperar = ($basePublica === '' ? '' : $basePublica) . '/recuperar-contrasena';
$urlApp = ($basePublica === '' ? '' : $basePublica) . '/app';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fabularia | Login</title>
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
            <p>Tu espacio para intercambiar libros con otros lectores. Publica, pide prestado y gestiona tus intercambios en un mismo panel.</p>
            <p class="pequeno">Accede con tu cuenta para entrar en la aplicaci&oacute;n.</p>
        </article>

        <article class="tarjeta auth-card">
            <h2>Iniciar sesi&oacute;n</h2>
            <form id="formularioLogin">
                <label for="loginEmail">Email</label>
                <input id="loginEmail" name="email" type="email" required>

                <label for="loginContrasena">Contrase&ntilde;a</label>
                <div class="campo-contrasena">
                    <input id="loginContrasena" name="contrasena" type="password" required>
                    <button type="button" class="toggle-contrasena" data-objetivo="loginContrasena" aria-label="Mostrar contrase&ntilde;a">&#128065;</button>
                </div>

                <button type="submit" style="margin-top:1rem;">Entrar</button>
            </form>

            <div id="mensaje" class="mensaje"></div>
            <div class="auth-enlaces auth-enlaces--stack">
                <a href="<?= htmlspecialchars($urlRecuperar, ENT_QUOTES) ?>">&iquest;Olvidaste tu contrase&ntilde;a?</a>
                <span class="pequeno">&iquest;No tienes cuenta? <a href="<?= htmlspecialchars($urlRegistro, ENT_QUOTES) ?>">Reg&iacute;strate aqu&iacute;</a>.</span>
            </div>
        </article>
    </section>
</main>

<script src="<?= htmlspecialchars($urlJs, ENT_QUOTES) ?>"></script>
<script>
    window.fabularia.configurar({
        urlBaseApi: <?= json_encode($urlBaseApi, JSON_UNESCAPED_SLASHES) ?>
    });

    const formulario = document.getElementById("formularioLogin");
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
        if (!texto) {
            return;
        }

        mensaje.className = `mensaje ${tipo}`;
        mensaje.textContent = texto;

        if (duracionMs > 0) {
            temporizadorMensaje = setTimeout(() => {
                limpiarMensaje();
            }, duracionMs);
        }
    }

    function inicializarToggleContrasena() {
        document.querySelectorAll(".toggle-contrasena").forEach((boton) => {
            boton.addEventListener("click", () => {
                const idObjetivo = boton.dataset.objetivo;
                const input = document.getElementById(idObjetivo);
                if (!input) return;

                const esPassword = input.type === "password";
                input.type = esPassword ? "text" : "password";
                boton.textContent = esPassword ? "\u{1F648}" : "\u{1F441}";
                boton.setAttribute("aria-label", esPassword ? "Ocultar contrase\u00F1a" : "Mostrar contrase\u00F1a");
            });
        });
    }

    formulario.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarMensaje();

        const datos = {
            email: formulario.email.value,
            contrasena: formulario.contrasena.value
        };

        try {
            await window.fabularia.llamarApi("/usuarios/login", "POST", datos);
            window.location.href = <?= json_encode($urlApp, JSON_UNESCAPED_SLASHES) ?>;
        } catch (error) {
            mostrarMensaje(error.message, "error", 5000);
        }
    });

    inicializarToggleContrasena();
</script>
</body>
</html>
