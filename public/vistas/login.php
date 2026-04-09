<?php

declare(strict_types=1);

$urlCss = ($basePublica === '' ? '' : $basePublica) . '/assets/css/estilos.css';
$urlJs = ($basePublica === '' ? '' : $basePublica) . '/assets/js/comun.js';
$urlLogo = ($basePublica === '' ? '' : $basePublica) . '/assets/img/logo-fabularia-solo-crop-web.png';
$urlRegistro = ($basePublica === '' ? '' : $basePublica) . '/registro';
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
            <p class="pequeno">Accede con tu cuenta para entrar en la aplicacion.</p>
        </article>

        <article class="tarjeta auth-card">
            <h2>Iniciar Sesion</h2>
            <form id="formularioLogin">
                <label for="loginEmail">Email</label>
                <input id="loginEmail" name="email" type="email" required>

                <label for="loginContrasena">Contrase&ntilde;a</label>
                <input id="loginContrasena" name="contrasena" type="password" required>

                <button type="submit" style="margin-top:1rem;">Entrar</button>
            </form>

            <div id="mensaje" class="mensaje"></div>
            <p class="pequeno">No tienes cuenta? <a href="<?= htmlspecialchars($urlRegistro, ENT_QUOTES) ?>">Registrate aqui</a>.</p>
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

    function mostrarMensaje(texto, tipo) {
        mensaje.className = `mensaje ${tipo}`;
        mensaje.textContent = texto;
    }

    formulario.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        mostrarMensaje("", "");

        const datos = {
            email: formulario.email.value,
            contrasena: formulario.contrasena.value
        };

        try {
            await window.fabularia.llamarApi("/usuarios/login", "POST", datos);
            window.location.href = <?= json_encode($urlApp, JSON_UNESCAPED_SLASHES) ?>;
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });
</script>
</body>
</html>

