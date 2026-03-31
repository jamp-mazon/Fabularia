<?php

declare(strict_types=1);

$basePublica = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
if ($basePublica === '.' || $basePublica === '') {
    $basePublica = '';
}
$urlBaseApi = $basePublica . '/api';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fabularia | Intercambio de Libros</title>
    <style>
        :root {
            --fondo: #f6f8fb;
            --panel: #ffffff;
            --texto: #1e293b;
            --acento: #0f766e;
            --acento-oscuro: #115e59;
            --error: #b91c1c;
            --ok: #166534;
            --borde: #d9e2ec;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: linear-gradient(160deg, #f8fbff 0%, #eef6f8 100%);
            color: var(--texto);
        }
        .contenedor {
            max-width: 1100px;
            margin: 0 auto;
            padding: 1.2rem;
        }
        h1, h2, h3 { margin-top: 0; }
        .tarjeta {
            background: var(--panel);
            border: 1px solid var(--borde);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }
        .rejilla {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }
        label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
            font-weight: 600;
        }
        input, textarea, button {
            width: 100%;
            padding: 0.65rem 0.75rem;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font: inherit;
        }
        textarea { min-height: 90px; resize: vertical; }
        button {
            border: 0;
            background: var(--acento);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover { background: var(--acento-oscuro); }
        .acciones {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }
        .acciones button {
            width: auto;
            min-width: 160px;
        }
        .mensaje {
            padding: 0.6rem 0.8rem;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            display: none;
        }
        .mensaje.error {
            display: block;
            background: #fee2e2;
            color: var(--error);
            border: 1px solid #fecaca;
        }
        .mensaje.ok {
            display: block;
            background: #dcfce7;
            color: var(--ok);
            border: 1px solid #bbf7d0;
        }
        .lista { list-style: none; margin: 0; padding: 0; }
        .lista li {
            border: 1px solid var(--borde);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.7rem;
            background: #fff;
        }
        .linea-encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }
        .secundario {
            background: #334155;
        }
        .secundario:hover {
            background: #1e293b;
        }
        .pequeno {
            font-size: 0.88rem;
            color: #475569;
        }
    </style>
</head>
<body>
<div class="contenedor">
    <h1>Fabularia</h1>
    <p>Intercambio de libros entre usuarios registrados.</p>

    <div id="mensaje" class="mensaje"></div>

    <section id="seccionAutenticacion" class="rejilla">
        <article class="tarjeta">
            <h2>Registro</h2>
            <form id="formularioRegistro">
                <label for="registroNombre">Nombre</label>
                <input id="registroNombre" name="nombre" required>

                <label for="registroApellidos">Apellidos</label>
                <input id="registroApellidos" name="apellidos" required>

                <label for="registroEmail">Email</label>
                <input id="registroEmail" name="email" type="email" required>

                <label for="registroContrasena">Contraseña</label>
                <input id="registroContrasena" name="contrasena" type="password" required>

                <button type="submit">Crear cuenta</button>
            </form>
        </article>

        <article class="tarjeta">
            <h2>Iniciar Sesión</h2>
            <form id="formularioLogin">
                <label for="loginEmail">Email</label>
                <input id="loginEmail" name="email" type="email" required>

                <label for="loginContrasena">Contraseña</label>
                <input id="loginContrasena" name="contrasena" type="password" required>

                <button type="submit">Entrar</button>
            </form>
        </article>
    </section>

    <section id="seccionAplicacion" style="display:none;">
        <article class="tarjeta">
            <div class="linea-encabezado">
                <div>
                    <h2>Panel de Usuario</h2>
                    <p class="pequeno">Sesión activa de: <strong id="nombreUsuarioActivo">-</strong></p>
                </div>
                <div class="acciones">
                    <button id="botonRefrescar" type="button" class="secundario">Refrescar datos</button>
                    <button id="botonCerrarSesion" type="button">Cerrar sesión</button>
                </div>
            </div>
        </article>

        <div class="rejilla">
            <article class="tarjeta">
                <h3>Publicar Libro para Intercambio</h3>
                <form id="formularioLibro">
                    <label for="libroTitulo">Título</label>
                    <input id="libroTitulo" name="titulo" required>

                    <label for="libroAutor">Autor</label>
                    <input id="libroAutor" name="autor" required>

                    <label for="libroGenero">Genero</label>
                    <input id="libroGenero" name="genero" placeholder="Ejemplo: Novela, Fantasia, Ciencia ficcion" required>

                    <label for="libroDescripcion">Descripción</label>
                    <textarea id="libroDescripcion" name="descripcion" placeholder="Estado, edición, notas..."></textarea>

                    <button type="submit">Publicar libro</button>
                </form>
            </article>

            <article class="tarjeta">
                <h3>Buscar Libros Disponibles</h3>
                <form id="formularioBusqueda">
                    <label for="busquedaTexto">Título o autor</label>
                    <input id="busquedaTexto" name="buscar" placeholder="Ejemplo: Borges">
                    <label for="busquedaGenero">Genero</label>
                    <input id="busquedaGenero" name="genero" placeholder="Filtrar por genero">
                    <button type="submit">Buscar</button>
                </form>
                <hr>
                <ul id="listaLibrosDisponibles" class="lista"></ul>
            </article>
        </div>

        <div class="rejilla">
            <article class="tarjeta">
                <h3>Mis Libros Publicados</h3>
                <ul id="listaMisLibros" class="lista"></ul>
            </article>

            <article class="tarjeta">
                <h3>Mis Préstamos</h3>
                <ul id="listaMisPrestamos" class="lista"></ul>
            </article>
        </div>
    </section>
</div>

<script>
    const URL_BASE_API = <?= json_encode($urlBaseApi, JSON_UNESCAPED_SLASHES) ?>;
    const elementoMensaje = document.getElementById("mensaje");
    const seccionAutenticacion = document.getElementById("seccionAutenticacion");
    const seccionAplicacion = document.getElementById("seccionAplicacion");
    const nombreUsuarioActivo = document.getElementById("nombreUsuarioActivo");

    function mostrarMensaje(texto, tipo = "ok") {
        elementoMensaje.className = "mensaje " + tipo;
        elementoMensaje.textContent = texto;
    }

    function limpiarMensaje() {
        elementoMensaje.className = "mensaje";
        elementoMensaje.textContent = "";
    }

    async function llamarApi(ruta, metodo = "GET", datos = null) {
        const opciones = { method: metodo, headers: {} };
        if (datos !== null) {
            opciones.headers["Content-Type"] = "application/json";
            opciones.body = JSON.stringify(datos);
        }

        const respuesta = await fetch(`${URL_BASE_API}${ruta}`, opciones);
        const cuerpo = await respuesta.json().catch(() => ({}));

        if (!respuesta.ok) {
            throw new Error(cuerpo.error || "Se produjo un error en la petición.");
        }

        return cuerpo;
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
            const descripcion = libro.descripcion ? `<p class="pequeno">${libro.descripcion}</p>` : "";
            elemento.innerHTML = `
                <strong>${libro.titulo}</strong> - ${libro.autor}
                <p class="pequeno">Genero: ${libro.genero}</p>
                <p class="pequeno">Propietario: ${libro.propietario}</p>
                ${descripcion}
                <button type="button" data-id-libro="${libro.id}">Solicitar préstamo</button>
            `;
            lista.appendChild(elemento);
        }
    }

    function renderizarMisLibros(libros) {
        const lista = document.getElementById("listaMisLibros");
        lista.innerHTML = "";

        if (!libros.length) {
            lista.innerHTML = "<li>No has publicado libros todavía.</li>";
            return;
        }

        for (const libro of libros) {
            const estado = Number(libro.disponible) === 1 ? "Disponible" : "Prestado";
            const elemento = document.createElement("li");
            elemento.innerHTML = `<strong>${libro.titulo}</strong> - ${libro.autor}<br><span class="pequeno">Genero: ${libro.genero}</span><br><span class="pequeno">Estado: ${estado}</span>`;
            lista.appendChild(elemento);
        }
    }

    function renderizarMisPrestamos(prestamos) {
        const lista = document.getElementById("listaMisPrestamos");
        lista.innerHTML = "";

        if (!prestamos.length) {
            lista.innerHTML = "<li>No tienes préstamos registrados.</li>";
            return;
        }

        for (const prestamo of prestamos) {
            const activo = prestamo.fecha_devolucion === null;
            const botonDevolver = activo
                ? `<button type="button" data-id-prestamo="${prestamo.id}">Marcar devolución</button>`
                : `<span class="pequeno">Devuelto: ${prestamo.fecha_devolucion}</span>`;

            const elemento = document.createElement("li");
            elemento.innerHTML = `
                <strong>${prestamo.titulo}</strong> - ${prestamo.autor}
                <p class="pequeno">Dueño: ${prestamo.nombre_dueno}</p>
                <p class="pequeno">Prestado: ${prestamo.fecha_prestamo}</p>
                ${botonDevolver}
            `;
            lista.appendChild(elemento);
        }
    }

    async function cargarLibrosDisponibles(termino = "", genero = "") {
        const parametros = new URLSearchParams();
        if (termino) {
            parametros.set("buscar", termino);
        }
        if (genero) {
            parametros.set("genero", genero);
        }
        const consulta = parametros.toString() ? `?${parametros.toString()}` : "";
        const datos = await llamarApi(`/libros${consulta}`);
        renderizarLibrosDisponibles(datos.libros || []);
    }

    async function cargarMisLibros() {
        const datos = await llamarApi("/libros/mios");
        renderizarMisLibros(datos.libros || []);
    }

    async function cargarMisPrestamos() {
        const datos = await llamarApi("/prestamos/mios");
        renderizarMisPrestamos(datos.prestamos || []);
    }

    async function cargarPanelCompleto() {
        await Promise.all([
            cargarLibrosDisponibles(),
            cargarMisLibros(),
            cargarMisPrestamos()
        ]);
    }

    async function actualizarSesion() {
        const datosSesion = await llamarApi("/usuarios/yo");
        if (!datosSesion.autenticado) {
            seccionAutenticacion.style.display = "grid";
            seccionAplicacion.style.display = "none";
            return false;
        }

        seccionAutenticacion.style.display = "none";
        seccionAplicacion.style.display = "block";
        nombreUsuarioActivo.textContent = `${datosSesion.usuario.nombre} ${datosSesion.usuario.apellidos}`;
        return true;
    }

    document.getElementById("formularioRegistro").addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarMensaje();

        const formulario = evento.currentTarget;
        const datos = {
            nombre: formulario.nombre.value,
            apellidos: formulario.apellidos.value,
            email: formulario.email.value,
            contrasena: formulario.contrasena.value
        };

        try {
            await llamarApi("/usuarios/registro", "POST", datos);
            mostrarMensaje("Registro correcto. Ya tienes sesión iniciada.");
            formulario.reset();
            await actualizarSesion();
            await cargarPanelCompleto();
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("formularioLogin").addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarMensaje();

        const formulario = evento.currentTarget;
        const datos = {
            email: formulario.email.value,
            contrasena: formulario.contrasena.value
        };

        try {
            await llamarApi("/usuarios/login", "POST", datos);
            mostrarMensaje("Sesión iniciada correctamente.");
            formulario.reset();
            await actualizarSesion();
            await cargarPanelCompleto();
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

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
            await llamarApi("/libros", "POST", datos);
            mostrarMensaje("Libro publicado para intercambio.");
            formulario.reset();
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
        if (!boton) {
            return;
        }

        limpiarMensaje();
        try {
            await llamarApi("/prestamos", "POST", { id_libro: Number(boton.dataset.idLibro) });
            mostrarMensaje("Préstamo solicitado correctamente.");
            await cargarPanelCompleto();
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("listaMisPrestamos").addEventListener("click", async (evento) => {
        const boton = evento.target.closest("button[data-id-prestamo]");
        if (!boton) {
            return;
        }

        limpiarMensaje();
        try {
            await llamarApi("/prestamos/devolver", "POST", { id_prestamo: Number(boton.dataset.idPrestamo) });
            mostrarMensaje("Préstamo devuelto correctamente.");
            await cargarPanelCompleto();
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("botonCerrarSesion").addEventListener("click", async () => {
        limpiarMensaje();
        try {
            await llamarApi("/usuarios/logout", "POST");
            mostrarMensaje("Sesión cerrada.");
            seccionAutenticacion.style.display = "grid";
            seccionAplicacion.style.display = "none";
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("botonRefrescar").addEventListener("click", async () => {
        limpiarMensaje();
        try {
            await cargarPanelCompleto();
            mostrarMensaje("Datos actualizados.");
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    (async () => {
        try {
            const autenticado = await actualizarSesion();
            if (autenticado) {
                await cargarPanelCompleto();
            }
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    })();
</script>
</body>
</html>
