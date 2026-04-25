<?php

declare(strict_types=1);

$token = trim((string) ($_GET['token'] ?? ''));
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
    <title>Fabularia | Restablecer contraseña</title>
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
            <p>Define una nueva contraseña para tu cuenta. Este enlace tiene una validez temporal.</p>
        </article>

        <article class="tarjeta auth-card">
            <h2>Restablecer contraseña</h2>
            <form id="formularioRestablecer">
                <input id="tokenRestablecimiento" name="token" type="hidden" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">

                <label for="nuevaContrasena">Nueva contraseña</label>
                <div class="campo-contrasena">
                    <input id="nuevaContrasena" name="contrasena_nueva" type="password" required>
                    <button type="button" class="toggle-contrasena" data-objetivo="nuevaContrasena" aria-label="Mostrar contraseña">👁</button>
                </div>

                <div class="fortaleza">
                    <div class="fortaleza-barra">
                        <div id="fortalezaProgreso" class="fortaleza-progreso"></div>
                    </div>
                    <div id="fortalezaTexto" class="fortaleza-texto">Seguridad: pendiente</div>
                </div>

                <label for="confirmarNuevaContrasena">Repetir contraseña</label>
                <div class="campo-contrasena">
                    <input id="confirmarNuevaContrasena" name="confirmar_contrasena" type="password" required>
                    <button type="button" class="toggle-contrasena" data-objetivo="confirmarNuevaContrasena" aria-label="Mostrar contraseña">👁</button>
                </div>

                <button type="submit" style="margin-top:1rem;">Actualizar contraseña</button>
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

    const formulario = document.getElementById("formularioRestablecer");
    const mensaje = document.getElementById("mensaje");
    const inputToken = document.getElementById("tokenRestablecimiento");
    const inputContrasena = document.getElementById("nuevaContrasena");
    const inputConfirmar = document.getElementById("confirmarNuevaContrasena");
    const fortalezaProgreso = document.getElementById("fortalezaProgreso");
    const fortalezaTexto = document.getElementById("fortalezaTexto");
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

    function inicializarToggleContrasena() {
        document.querySelectorAll(".toggle-contrasena").forEach((boton) => {
            boton.addEventListener("click", () => {
                const idObjetivo = boton.dataset.objetivo;
                const input = document.getElementById(idObjetivo);
                if (!input) return;

                const esPassword = input.type === "password";
                input.type = esPassword ? "text" : "password";
                boton.textContent = esPassword ? "🙈" : "👁";
                boton.setAttribute("aria-label", esPassword ? "Ocultar contraseña" : "Mostrar contraseña");
            });
        });
    }

    function evaluarFortaleza(contrasena) {
        let puntuacion = 0;
        if (contrasena.length >= 8) puntuacion += 1;
        if (contrasena.length >= 12) puntuacion += 1;
        if (/[a-z]/.test(contrasena) && /[A-Z]/.test(contrasena)) puntuacion += 1;
        if (/\d/.test(contrasena)) puntuacion += 1;
        if (/[^A-Za-z0-9]/.test(contrasena)) puntuacion += 1;

        if (contrasena.length === 0) {
            return { porcentaje: 0, clase: "", texto: "Seguridad: pendiente" };
        }

        if (puntuacion <= 2) {
            return { porcentaje: 33, clase: "debil", texto: "Seguridad: débil" };
        }

        if (puntuacion <= 4) {
            return { porcentaje: 66, clase: "media", texto: "Seguridad: media" };
        }

        return { porcentaje: 100, clase: "fuerte", texto: "Seguridad: fuerte" };
    }

    function refrescarFortaleza() {
        const resultado = evaluarFortaleza(inputContrasena.value);
        fortalezaProgreso.style.width = `${resultado.porcentaje}%`;
        fortalezaProgreso.classList.remove("debil", "media", "fuerte");
        if (resultado.clase) {
            fortalezaProgreso.classList.add(resultado.clase);
        }
        fortalezaTexto.textContent = resultado.texto;
    }

    inputContrasena.addEventListener("input", refrescarFortaleza);

    formulario.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarMensaje();

        const token = inputToken.value.trim();
        if (!token) {
            mostrarMensaje("El enlace de restablecimiento no es válido.", "error", 6000);
            return;
        }

        if (inputContrasena.value !== inputConfirmar.value) {
            mostrarMensaje("Las contraseñas no coinciden.", "error", 5000);
            return;
        }

        try {
            const respuesta = await window.fabularia.llamarApi("/usuarios/restablecer-contrasena", "POST", {
                token,
                contrasena_nueva: inputContrasena.value,
                confirmar_contrasena: inputConfirmar.value
            });

            mostrarMensaje(respuesta.mensaje || "Contraseña actualizada.", "ok", 2500);
            setTimeout(() => {
                window.location.href = <?= json_encode($urlLogin, JSON_UNESCAPED_SLASHES) ?>;
            }, 1200);
        } catch (error) {
            mostrarMensaje(error.message, "error", 6000);
        }
    });

    inicializarToggleContrasena();
    refrescarFortaleza();

    if (!inputToken.value.trim()) {
        mostrarMensaje("El enlace de restablecimiento no es válido o está incompleto.", "error", 7000);
    }
</script>
</body>
</html>