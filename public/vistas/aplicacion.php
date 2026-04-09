<?php

declare(strict_types=1);

$urlCss = ($basePublica === '' ? '' : $basePublica) . '/assets/css/estilos.css';
$urlJs = ($basePublica === '' ? '' : $basePublica) . '/assets/js/comun.js';
$urlLogin = ($basePublica === '' ? '' : $basePublica) . '/login';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fabularia | Aplicacion</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($urlCss, ENT_QUOTES) ?>">
</head>
<body>
<main class="pagina">
    <header class="cabecera-simple">
        <div>
            <div class="marca">Fabularia</div>
            <p class="subtitulo">Intercambio de libros entre lectores</p>
        </div>
        <div class="fila-botones">
            <button id="botonRefrescar" class="boton-secundario" type="button">Refrescar</button>
            <button id="botonCerrarSesion" type="button">Cerrar sesion</button>
        </div>
    </header>

    <article class="tarjeta panel-card">
        <h3>Panel de Usuario</h3>
        <p class="pequeno">Sesion activa: <strong id="nombreUsuarioActivo">-</strong></p>
        <p class="pequeno">
            Vincular Telegram:
            <a id="enlaceTelegramPanel" href="#" target="_blank" rel="noopener noreferrer">Abrir bot</a>
        </p>
        <p id="estadoTelegram" class="pequeno"></p>
        <div id="mensaje" class="mensaje"></div>
    </article>

    <section class="panel-grid">
        <article class="tarjeta panel-card">
            <h3>Publicar Libro</h3>
            <form id="formularioLibro">
                <label for="libroTitulo">Titulo</label>
                <input id="libroTitulo" name="titulo" required>

                <label for="libroAutor">Autor</label>
                <input id="libroAutor" name="autor" required>

                <label for="libroGenero">Genero</label>
                <input id="libroGenero" name="genero" placeholder="Novela, poesia, ciencia..." required>

                <label for="libroDescripcion">Descripcion</label>
                <textarea id="libroDescripcion" name="descripcion" placeholder="Estado del libro, edicion, etc."></textarea>

                <button type="submit" style="margin-top:0.8rem;">Guardar libro</button>
            </form>
        </article>

        <article class="tarjeta panel-card">
            <h3>Buscar Disponibles</h3>
            <form id="formularioBusqueda">
                <label for="busquedaTexto">Titulo o autor</label>
                <input id="busquedaTexto" name="buscar">

                <label for="busquedaGenero">Genero</label>
                <input id="busquedaGenero" name="genero">

                <button type="submit" style="margin-top:0.8rem;">Buscar</button>
            </form>
            <hr class="separador">
            <ul id="listaLibrosDisponibles" class="lista"></ul>
        </article>
    </section>

    <section class="panel-grid" style="margin-top:1rem;">
        <article class="tarjeta panel-card">
            <h3>Mis Libros</h3>
            <ul id="listaMisLibros" class="lista"></ul>
        </article>

        <article class="tarjeta panel-card">
            <h3>Mis Prestamos</h3>
            <ul id="listaMisPrestamos" class="lista"></ul>
        </article>
    </section>
</main>

<script src="<?= htmlspecialchars($urlJs, ENT_QUOTES) ?>"></script>
<script>
    window.fabularia.configurar({
        urlBaseApi: <?= json_encode($urlBaseApi, JSON_UNESCAPED_SLASHES) ?>,
        urlBaseTelegram: <?= json_encode($urlBaseTelegramBot, JSON_UNESCAPED_SLASHES) ?>
    });

    const mensaje = document.getElementById("mensaje");
    const nombreUsuarioActivo = document.getElementById("nombreUsuarioActivo");
    const enlaceTelegramPanel = document.getElementById("enlaceTelegramPanel");
    const estadoTelegram = document.getElementById("estadoTelegram");

    function mostrarMensaje(texto, tipo = "ok") {
        mensaje.className = `mensaje ${tipo}`;
        mensaje.textContent = texto;
    }

    function limpiarMensaje() {
        mensaje.className = "mensaje";
        mensaje.textContent = "";
    }

    function renderizarLibrosDisponibles(libros) {
        const lista = document.getElementById("listaLibrosDisponibles");
        lista.innerHTML = "";
        if (!libros.length) {
            lista.innerHTML = "<li>No hay libros disponibles con esos filtros.</li>";
            return;
        }

        for (const libro of libros) {
            const elemento = document.createElement("li");
            const descripcion = libro.descripcion ? `<p class='pequeno'>${libro.descripcion}</p>` : "";
            elemento.innerHTML = `
                <strong>${libro.titulo}</strong> - ${libro.autor}
                <p class="pequeno">Genero: ${libro.genero}</p>
                <p class="pequeno">Propietario: ${libro.propietario}</p>
                ${descripcion}
                <button type="button" data-id-libro="${libro.id}">Solicitar prestamo</button>
            `;
            lista.appendChild(elemento);
        }
    }

    function renderizarMisLibros(libros) {
        const lista = document.getElementById("listaMisLibros");
        lista.innerHTML = "";
        if (!libros.length) {
            lista.innerHTML = "<li>No has publicado libros todavia.</li>";
            return;
        }

        for (const libro of libros) {
            const estado = Number(libro.disponible) === 1 ? "Disponible" : "Prestado";
            const elemento = document.createElement("li");
            elemento.innerHTML = `
                <strong>${libro.titulo}</strong> - ${libro.autor}
                <p class="pequeno">Genero: ${libro.genero}</p>
                <p class="pequeno">Estado: ${estado}</p>
            `;
            lista.appendChild(elemento);
        }
    }

    function renderizarMisPrestamos(prestamos) {
        const lista = document.getElementById("listaMisPrestamos");
        lista.innerHTML = "";
        if (!prestamos.length) {
            lista.innerHTML = "<li>No tienes prestamos registrados.</li>";
            return;
        }

        for (const prestamo of prestamos) {
            const activo = prestamo.fecha_devolucion === null;
            const botonDevolver = activo
                ? `<button type="button" data-id-prestamo="${prestamo.id}">Marcar devolucion</button>`
                : `<span class="pequeno">Devuelto: ${prestamo.fecha_devolucion}</span>`;

            const elemento = document.createElement("li");
            elemento.innerHTML = `
                <strong>${prestamo.titulo}</strong> - ${prestamo.autor}
                <p class="pequeno">Dueno: ${prestamo.nombre_dueno}</p>
                <p class="pequeno">Prestado: ${prestamo.fecha_prestamo}</p>
                ${botonDevolver}
            `;
            lista.appendChild(elemento);
        }
    }

    async function cargarLibrosDisponibles(termino = "", genero = "") {
        const parametros = new URLSearchParams();
        if (termino) parametros.set("buscar", termino);
        if (genero) parametros.set("genero", genero);
        const consulta = parametros.toString() ? `?${parametros.toString()}` : "";
        const datos = await window.fabularia.llamarApi(`/libros${consulta}`);
        renderizarLibrosDisponibles(datos.libros || []);
    }

    async function cargarMisLibros() {
        const datos = await window.fabularia.llamarApi("/libros/mios");
        renderizarMisLibros(datos.libros || []);
    }

    async function cargarMisPrestamos() {
        const datos = await window.fabularia.llamarApi("/prestamos/mios");
        renderizarMisPrestamos(datos.prestamos || []);
    }

    async function actualizarSesion() {
        const datosSesion = await window.fabularia.llamarApi("/usuarios/yo");
        if (!datosSesion.autenticado) {
            window.location.href = <?= json_encode($urlLogin, JSON_UNESCAPED_SLASHES) ?>;
            return null;
        }

        const usuario = datosSesion.usuario;
        nombreUsuarioActivo.textContent = `${usuario.nombre} ${usuario.apellidos}`;
        enlaceTelegramPanel.href = window.fabularia.enlaceTelegram(usuario.id);
        estadoTelegram.textContent = usuario.telegram_chat_id
            ? `Telegram vinculado (chat_id: ${usuario.telegram_chat_id}).`
            : "Telegram aun no vinculado.";
        return usuario;
    }

    async function cargarPanelCompleto() {
        await Promise.all([cargarLibrosDisponibles(), cargarMisLibros(), cargarMisPrestamos()]);
    }

    document.getElementById("formularioLibro").addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarMensaje();
        const formulario = evento.currentTarget;
        const datos = {
            titulo: formulario.titulo.value,
            autor: formulario.autor.value,
            genero: formulario.genero.value,
            descripcion: formulario.descripcion.value
        };

        try {
            await window.fabularia.llamarApi("/libros", "POST", datos);
            formulario.reset();
            mostrarMensaje("Libro guardado correctamente.", "ok");
            await cargarPanelCompleto();
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("formularioBusqueda").addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarMensaje();
        const termino = evento.currentTarget.buscar.value.trim();
        const genero = evento.currentTarget.genero.value.trim();

        try {
            await cargarLibrosDisponibles(termino, genero);
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("listaLibrosDisponibles").addEventListener("click", async (evento) => {
        const boton = evento.target.closest("button[data-id-libro]");
        if (!boton) return;

        limpiarMensaje();
        try {
            await window.fabularia.llamarApi("/prestamos", "POST", { id_libro: Number(boton.dataset.idLibro) });
            mostrarMensaje("Prestamo solicitado correctamente.", "ok");
            await cargarPanelCompleto();
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("listaMisPrestamos").addEventListener("click", async (evento) => {
        const boton = evento.target.closest("button[data-id-prestamo]");
        if (!boton) return;

        limpiarMensaje();
        try {
            await window.fabularia.llamarApi("/prestamos/devolver", "POST", { id_prestamo: Number(boton.dataset.idPrestamo) });
            mostrarMensaje("Prestamo devuelto correctamente.", "ok");
            await cargarPanelCompleto();
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("botonRefrescar").addEventListener("click", async () => {
        limpiarMensaje();
        try {
            await actualizarSesion();
            await cargarPanelCompleto();
            mostrarMensaje("Datos actualizados.", "ok");
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("botonCerrarSesion").addEventListener("click", async () => {
        limpiarMensaje();
        try {
            await window.fabularia.llamarApi("/usuarios/logout", "POST");
            window.location.href = <?= json_encode($urlLogin, JSON_UNESCAPED_SLASHES) ?>;
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    (async () => {
        try {
            await actualizarSesion();
            await cargarPanelCompleto();
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    })();
</script>
</body>
</html>
