<?php

declare(strict_types=1);

$urlCss = ($basePublica === '' ? '' : $basePublica) . '/assets/css/estilos.css';
$urlJs = ($basePublica === '' ? '' : $basePublica) . '/assets/js/comun.js';
$urlLogo = ($basePublica === '' ? '' : $basePublica) . '/assets/img/logo-fabularia-solo-crop-web.png';
$urlLogin = ($basePublica === '' ? '' : $basePublica) . '/login';
$urlApp = ($basePublica === '' ? '' : $basePublica) . '/app';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fabularia | Registro</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($urlCss, ENT_QUOTES) ?>">
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($urlLogo, ENT_QUOTES) ?>">
</head>
<body>
<main class="pagina">
    <section class="auth-layout">
        <article class="tarjeta info-card">
            <div class="marca-bloque marca-bloque--auth">
                <img class="marca-logo marca-logo--auth" src="<?= htmlspecialchars($urlLogo, ENT_QUOTES) ?>" alt="Logo de Fabularia">
                <p class="marca-titulo">Fabularia</p>
            </div>
            <h1>Crea tu cuenta</h1>
            <p>Registra tus datos y publica los libros que quieres intercambiar. Tambien puedes vincular Telegram para recibir avisos cuando te pidan un libro.</p>
        </article>

        <article class="tarjeta auth-card">
            <h2>Registro</h2>
            <form id="formularioRegistro">
                <label for="registroNombre">Nombre</label>
                <input id="registroNombre" name="nombre" required>

                <label for="registroApellidos">Apellidos</label>
                <input id="registroApellidos" name="apellidos" required>

                <label for="registroEmail">Email</label>
                <input id="registroEmail" name="email" type="email" required>

                <label for="registroTelefono">Telefono (opcional)</label>
                <input id="registroTelefono" name="telefono" type="text" placeholder="+34 600 000 000">

                <label for="registroContrasena">Contrase&ntilde;a</label>
                <input id="registroContrasena" name="contrasena" type="password" required>

                <label class="fila-check">
                    <input id="registroVincularTelegram" name="vincular_telegram" type="checkbox">
                    <span>Quiero vincular mi Telegram</span>
                </label>

                <button type="submit" style="margin-top:0.5rem;">Crear cuenta</button>
            </form>

            <div id="mensaje" class="mensaje"></div>
            <p id="bloqueTelegram" class="pequeno" style="display:none;">
                Vincula tu cuenta aqui:
                <a id="enlaceTelegram" href="#" target="_blank" rel="noopener noreferrer">Abrir bot</a>
            </p>
            <p class="pequeno">Ya tienes cuenta? <a href="<?= htmlspecialchars($urlLogin, ENT_QUOTES) ?>">Inicia sesion</a>.</p>
        </article>
    </section>
</main>

<script src="<?= htmlspecialchars($urlJs, ENT_QUOTES) ?>"></script>
<script>
    window.fabularia.configurar({
        urlBaseApi: <?= json_encode($urlBaseApi, JSON_UNESCAPED_SLASHES) ?>,
        urlBaseTelegram: <?= json_encode($urlBaseTelegramBot, JSON_UNESCAPED_SLASHES) ?>
    });

    const formulario = document.getElementById("formularioRegistro");
    const mensaje = document.getElementById("mensaje");
    const bloqueTelegram = document.getElementById("bloqueTelegram");
    const enlaceTelegram = document.getElementById("enlaceTelegram");

    function mostrarMensaje(texto, tipo) {
        mensaje.className = `mensaje ${tipo}`;
        mensaje.textContent = texto;
    }

    formulario.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        mostrarMensaje("", "");

        const datos = {
            nombre: formulario.nombre.value,
            apellidos: formulario.apellidos.value,
            telefono: formulario.telefono.value.trim(),
            email: formulario.email.value,
            contrasena: formulario.contrasena.value
        };

        try {
            const respuesta = await window.fabularia.llamarApi("/usuarios/registro", "POST", datos);
            mostrarMensaje("Cuenta creada correctamente.", "ok");

            if (formulario.vincular_telegram.checked) {
                enlaceTelegram.href = window.fabularia.enlaceTelegram(respuesta.usuario.id);
                bloqueTelegram.style.display = "block";
            } else {
                bloqueTelegram.style.display = "none";
            }

            setTimeout(() => {
                window.location.href = <?= json_encode($urlApp, JSON_UNESCAPED_SLASHES) ?>;
            }, 800);
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });
</script>
</body>
</html>


