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
    <title>Fabularia | Aplicacion</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($urlCss, ENT_QUOTES) ?>">
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($urlLogo, ENT_QUOTES) ?>">
</head>
<body>
<main class="pagina">
    <header class="cabecera-simple cabecera-simple--app">
        <div class="cabecera-col cabecera-col--marca">
            <div class="marca-bloque marca-bloque--app">
                <img class="marca-logo marca-logo--app" src="<?= htmlspecialchars($urlLogo, ENT_QUOTES) ?>" alt="Logo de Fabularia">
                <div class="marca">Fabularia</div>
            </div>
            <p class="subtitulo">Intercambio de libros entre lectores</p>
        </div>

        <div class="fila-botones fila-botones--cabecera">
            <div class="acceso-usuario">
                <p class="bienvenida-inline"><span>Bienvenido,</span> <strong id="nombreUsuarioActivo">-</strong></p>
                <button id="botonCerrarSesionRapido" type="button">Cerrar sesion</button>
            </div>
            <div id="mensaje" class="mensaje mensaje--inline"></div>
        </div>
    </header>

    <section class="tarjeta panel-card app-main app-main--principal">
        <nav class="tab-nav" aria-label="Secciones de aplicacion">
                <button type="button" class="tab-btn tab-btn--activa" data-tab="catalogo">Catalogo</button>
                <button type="button" class="tab-btn" data-tab="publicar">Publicar</button>
                <button type="button" class="tab-btn" data-tab="mis-libros">Mis libros</button>
                <button type="button" class="tab-btn" data-tab="prestamos">Prestamos</button>
                <button type="button" class="tab-btn tab-btn--icono" data-tab="ajustes" aria-label="Ajustes">
                    <span class="icono-ajustes-emoji" aria-hidden="true">&#9881;</span>
                    <span>Ajustes</span>
                </button>
            </nav>

            <section class="tab-panel tab-panel--activo" data-panel="catalogo">
                <article class="subcard catalogo-panel">
                    <h3>Catalogo disponible</h3>
                    <form id="formularioBusqueda" class="catalogo-filtros">
                        <div class="catalogo-filtro-grupo">
                            <label for="busquedaTexto">Titulo o autor</label>
                            <input id="busquedaTexto" name="buscar" placeholder="Busca por titulo o autor...">
                        </div>

                        <div class="catalogo-filtro-grupo">
                            <label for="busquedaGenero">Genero</label>
                            <input id="busquedaGenero" name="genero" placeholder="Fantasia, novela, poesia...">
                        </div>

                        <button type="submit">Buscar</button>
                    </form>

                    <p id="estadoResultadosCatalogo" class="pequeno catalogo-estado"></p>
                    <ul id="listaLibrosDisponibles" class="catalogo-grid"></ul>
                </article>
            </section>

            <section class="tab-panel" data-panel="publicar">
                <article class="subcard subcard--compacta">
                    <h3>Publicar libro</h3>
                    <div class="catalogo-busqueda">
                        <label for="busquedaCatalogoGlobal">Buscar en catalogo global</label>
                        <div class="catalogo-busqueda-fila">
                            <input id="busquedaCatalogoGlobal" type="text" placeholder="Escribe un titulo para sugerencias...">
                            <button id="botonBuscarCatalogoGlobal" type="button" class="boton-secundario">Buscar</button>
                        </div>
                        <ul id="listaSugerenciasCatalogo" class="lista lista-sugerencias"></ul>
                    </div>

                    <form id="formularioLibro">
                        <label for="libroTitulo">Titulo</label>
                        <input id="libroTitulo" name="titulo" required>

                        <label for="libroAutor">Autor</label>
                        <input id="libroAutor" name="autor" required>

                        <label for="libroGenero">Genero</label>
                        <input id="libroGenero" name="genero" placeholder="Novela, poesia, ciencia..." required>

                        <label for="libroPortada">Portada (URL, opcional)</label>
                        <input id="libroPortada" name="portada_url" type="url" placeholder="https://...">

                        <label for="libroDescripcion">Descripcion</label>
                        <textarea id="libroDescripcion" name="descripcion" placeholder="Estado del libro, edicion, etc."></textarea>

                        <button type="submit" style="margin-top:0.8rem;">Guardar libro</button>
                    </form>
                </article>
            </section>

            <section class="tab-panel" data-panel="mis-libros">
                <article class="subcard subcard--compacta">
                    <h3>Mis libros</h3>
                    <ul id="listaMisLibros" class="lista"></ul>
                </article>
            </section>

            <section class="tab-panel" data-panel="prestamos">
                <article class="subcard subcard--compacta">
                    <h3>Mis prestamos</h3>
                    <ul id="listaMisPrestamos" class="lista"></ul>
                </article>
            </section>

            <section class="tab-panel" data-panel="ajustes">
                <div class="ajustes-grid">
                    <article class="subcard">
                        <h3>Telegram</h3>
                        <p id="estadoTelegramAjustes" class="estado-telegram estado-telegram--pendiente">Telegram no vinculado</p>
                        <p id="textoTelegramUsuario" class="pequeno">Vincula tu cuenta para recibir avisos de prestamos.</p>

                        <div class="fila-botones ajustes-botones">
                            <a id="enlaceTelegramAjustes" class="telegram-link telegram-link--boton" href="#" target="_blank" rel="noopener noreferrer">
                                <span class="telegram-logo" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21.4 4.6L18.6 18.1C18.4 19 17.9 19.2 17.1 18.8L12.7 15.6L10.6 17.6C10.4 17.8 10.2 18 9.8 18L10.1 13.4L18.3 6C18.7 5.7 18.2 5.5 17.7 5.8L7.5 12.2L3.1 10.9C2.1 10.6 2.1 9.9 3.3 9.5L20.3 3C21.1 2.7 21.8 3.2 21.4 4.6Z" fill="currentColor"/>
                                    </svg>
                                </span>
                                <span>Abrir chat del bot</span>
                            </a>
                            <button id="botonDesvincularTelegram" type="button" class="boton-secundario">Desvincular Telegram</button>
                        </div>
                    </article>

                    <article class="subcard">
                        <h3>Seguridad</h3>
                        <form id="formularioTelefono" class="formulario-telefono">
                            <label for="telefonoUsuarioAjustes">Tel&eacute;fono</label>
                            <div class="fila-corta">
                                <input id="telefonoUsuarioAjustes" name="telefono" type="text" placeholder="+34 600 000 000">
                                <button type="submit" class="boton-secundario">Guardar tel&eacute;fono</button>
                            </div>
                        </form>

                        <form id="formularioCambiarContrasena">
                            <label for="contrasenaActual">Contrase&ntilde;a actual</label>
                            <input id="contrasenaActual" name="contrasena_actual" type="password" required>

                            <label for="contrasenaNueva">Nueva contrase&ntilde;a</label>
                            <input id="contrasenaNueva" name="contrasena_nueva" type="password" minlength="6" required>

                            <label for="confirmarContrasenaNueva">Confirmar nueva contrase&ntilde;a</label>
                            <input id="confirmarContrasenaNueva" name="confirmar_contrasena" type="password" minlength="6" required>

                            <button type="submit" style="margin-top:0.8rem;">Actualizar contrase&ntilde;a</button>
                        </form>
                    </article>

                    <article class="subcard subcard--peligro">
                        <h3>Cuenta</h3>
                        <p class="pequeno">Puedes eliminar tu cuenta de forma permanente.</p>

                        <hr class="separador">

                        <form id="formularioEliminarCuenta">
                            <label for="contrasenaEliminar">Contrase&ntilde;a para eliminar cuenta</label>
                            <input id="contrasenaEliminar" name="contrasena" type="password" required>

                            <label class="fila-check" for="confirmacionEliminarCuenta">
                                <input id="confirmacionEliminarCuenta" name="confirmacion" type="checkbox" required>
                                Confirmo que quiero eliminar mi cuenta y sus datos.
                            </label>

                            <button id="botonEliminarCuenta" type="submit" class="boton-peligro">Eliminar cuenta</button>
                        </form>
                    </article>
                </div>
            </section>
    </section>
</main>

<div id="modalDetalleLibro" class="modal-libro" hidden>
    <div class="modal-libro-contenido" role="dialog" aria-modal="true" aria-labelledby="detalleLibroTitulo">
        <button id="cerrarDetalleLibro" type="button" class="modal-cerrar" aria-label="Cerrar detalle">&times;</button>
        <div class="detalle-libro-layout">
            <div id="detalleLibroPortada" class="detalle-libro-portada"></div>
            <div class="detalle-libro-texto">
                <h3 id="detalleLibroTitulo"></h3>
                <p id="detalleLibroMeta" class="pequeno"></p>
                <p id="detalleLibroPropietario" class="pequeno"></p>
                <p id="detalleLibroDescripcion" class="detalle-libro-descripcion"></p>
                <div class="detalle-libro-acciones">
                    <button id="botonSolicitarDetalle" type="button">Solicitar prestamo</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars($urlJs, ENT_QUOTES) ?>"></script>
<script>
    window.fabularia.configurar({
        urlBaseApi: <?= json_encode($urlBaseApi, JSON_UNESCAPED_SLASHES) ?>,
        urlBaseTelegram: <?= json_encode($urlBaseTelegramBot, JSON_UNESCAPED_SLASHES) ?>
    });

    const mensaje = document.getElementById("mensaje");
    const nombreUsuarioActivo = document.getElementById("nombreUsuarioActivo");
    const telefonoUsuarioAjustes = document.getElementById("telefonoUsuarioAjustes");
    const enlaceTelegramAjustes = document.getElementById("enlaceTelegramAjustes");
    const estadoTelegramAjustes = document.getElementById("estadoTelegramAjustes");
    const textoTelegramUsuario = document.getElementById("textoTelegramUsuario");
    const botonDesvincularTelegram = document.getElementById("botonDesvincularTelegram");
    const inputBusquedaCatalogo = document.getElementById("busquedaCatalogoGlobal");
    const listaSugerenciasCatalogo = document.getElementById("listaSugerenciasCatalogo");
    const listaLibrosDisponibles = document.getElementById("listaLibrosDisponibles");
    const estadoResultadosCatalogo = document.getElementById("estadoResultadosCatalogo");
    const modalDetalleLibro = document.getElementById("modalDetalleLibro");
    const cerrarDetalleLibro = document.getElementById("cerrarDetalleLibro");
    const detalleLibroPortada = document.getElementById("detalleLibroPortada");
    const detalleLibroTitulo = document.getElementById("detalleLibroTitulo");
    const detalleLibroMeta = document.getElementById("detalleLibroMeta");
    const detalleLibroPropietario = document.getElementById("detalleLibroPropietario");
    const detalleLibroDescripcion = document.getElementById("detalleLibroDescripcion");
    const botonSolicitarDetalle = document.getElementById("botonSolicitarDetalle");
    const librosCatalogoPorId = new Map();
    const prestamosPorId = new Map();
    let temporizadorMensaje = null;

    function escaparHtml(texto) {
        return String(texto ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#39;");
    }

    function mostrarMensaje(texto, tipo = "ok", duracionMs = 5000) {
        if (temporizadorMensaje !== null) {
            clearTimeout(temporizadorMensaje);
            temporizadorMensaje = null;
        }

        mensaje.className = `mensaje ${tipo}`;
        mensaje.textContent = texto;

        if (texto.trim() !== "" && duracionMs > 0) {
            temporizadorMensaje = setTimeout(() => {
                limpiarMensaje();
            }, duracionMs);
        }
    }

    function limpiarMensaje() {
        if (temporizadorMensaje !== null) {
            clearTimeout(temporizadorMensaje);
            temporizadorMensaje = null;
        }

        mensaje.className = "mensaje";
        mensaje.textContent = "";
    }

    function activarTab(nombreTab) {
        document.querySelectorAll(".tab-btn").forEach((boton) => {
            boton.classList.toggle("tab-btn--activa", boton.dataset.tab === nombreTab);
        });

        document.querySelectorAll(".tab-panel").forEach((panel) => {
            panel.classList.toggle("tab-panel--activo", panel.dataset.panel === nombreTab);
        });
    }

    function pintarEstadoTelegram(usuario) {
        enlaceTelegramAjustes.href = window.fabularia.enlaceTelegram(usuario.id);

        if (usuario.telegram_chat_id) {
            estadoTelegramAjustes.className = "estado-telegram estado-telegram--ok";
            estadoTelegramAjustes.textContent = `Telegram vinculado (chat_id: ${usuario.telegram_chat_id})`;

            if (usuario.telegram_usuario) {
                textoTelegramUsuario.textContent = `Usuario Telegram: @${usuario.telegram_usuario}`;
            } else {
                textoTelegramUsuario.textContent = "Usuario Telegram vinculado sin alias publico.";
            }

            botonDesvincularTelegram.disabled = false;
        } else {
            estadoTelegramAjustes.className = "estado-telegram estado-telegram--pendiente";
            estadoTelegramAjustes.textContent = "Telegram no vinculado";
            textoTelegramUsuario.textContent = "Vincula tu cuenta para recibir avisos de prestamos.";
            botonDesvincularTelegram.disabled = true;
        }
    }

    function bloquePortada(libro) {
        const url = (libro.portada_url || "").trim();
        const titulo = escaparHtml(libro.titulo || "");

        if (!url) {
            return `<div class="portada-wrap"><div class="portada-fallback">Sin portada</div></div>`;
        }

        return `
            <div class="portada-wrap">
                <img src="${escaparHtml(url)}" alt="Portada de ${titulo}" loading="lazy"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                <div class="portada-fallback" style="display:none;">Sin portada</div>
            </div>
        `;
    }

    function limpiarSugerenciasCatalogo() {
        listaSugerenciasCatalogo.innerHTML = "";
    }

    function aplicarSugerenciaEnFormulario(sugerencia) {
        const formulario = document.getElementById("formularioLibro");
        formulario.titulo.value = sugerencia.titulo || "";
        formulario.autor.value = sugerencia.autor || "";
        formulario.genero.value = sugerencia.genero || "";
        formulario.portada_url.value = sugerencia.portada_url || "";
        formulario.descripcion.value = soloDescripcionEspanol(sugerencia.descripcion || "");
    }

    function renderizarSugerenciasCatalogo(sugerencias) {
        limpiarSugerenciasCatalogo();
        if (!sugerencias.length) {
            listaSugerenciasCatalogo.innerHTML = "<li>No hay sugerencias para ese texto.</li>";
            return;
        }

        for (const sugerencia of sugerencias) {
            const item = document.createElement("li");
            item.className = "sugerencia-item";

            item.innerHTML = `
                <button type="button" class="sugerencia-btn">
                    ${bloquePortada(sugerencia)}
                    <div>
                        <strong>${escaparHtml(sugerencia.titulo)} - ${escaparHtml(sugerencia.autor)}</strong>
                        <p class="pequeno">Genero: ${escaparHtml(sugerencia.genero || "General")}</p>
                    </div>
                </button>
            `;

            item.querySelector("button").addEventListener("click", () => {
                aplicarSugerenciaEnFormulario(sugerencia);
                limpiarSugerenciasCatalogo();
                activarTab("publicar");
                mostrarMensaje("Campos autocompletados desde el catalogo global.", "ok");
            });

            listaSugerenciasCatalogo.appendChild(item);
        }
    }

    function pareceTextoEnIngles(texto) {
        const palabras = String(texto ?? "")
            .toLowerCase()
            .split(/[^a-z]+/g)
            .filter(Boolean);

        if (palabras.length < 12) {
            return false;
        }

        const comunes = new Set([
            "the", "and", "with", "that", "this", "from", "into", "your", "their", "about", "over",
            "under", "have", "has", "been", "will", "would", "could", "should", "first", "before",
            "until", "where", "when", "then", "while", "is", "are", "was", "were", "to", "of", "in",
            "on", "for", "as", "at", "by", "it", "its", "he", "she", "they", "him", "her", "them"
        ]);

        let coincidencias = 0;
        for (const palabra of palabras) {
            if (comunes.has(palabra)) {
                coincidencias += 1;
            }
        }

        return coincidencias >= 6 || (coincidencias / palabras.length) >= 0.22;
    }

    function soloDescripcionEspanol(texto) {
        let limpio = String(texto ?? "").trim();
        if (!limpio) {
            return "";
        }

        const marcadorIngles = limpio.match(/\b(?:ENGLISH DESCRIPTION|DESCRIPTION IN ENGLISH|ENGLISH VERSION)\b/i);
        if (marcadorIngles && typeof marcadorIngles.index === "number") {
            limpio = limpio.slice(0, marcadorIngles.index).trim();
        }

        const bloques = limpio
            .split(/\n{2,}/)
            .map((bloque) => bloque.trim())
            .filter((bloque) => bloque !== "");

        while (bloques.length > 1 && pareceTextoEnIngles(bloques[bloques.length - 1])) {
            bloques.pop();
        }

        return bloques.join("\n\n").trim();
    }

    function recortarTexto(texto, maximoCaracteres = 180) {
        const limpio = soloDescripcionEspanol(texto);
        if (limpio.length <= maximoCaracteres) {
            return limpio;
        }

        return `${limpio.slice(0, maximoCaracteres).trimEnd()}...`;
    }

    function abrirDetalleLibro(idLibro) {
        const libro = librosCatalogoPorId.get(idLibro);
        if (!libro) {
            mostrarMensaje("No se encontro la ficha del libro seleccionado.", "error");
            return;
        }

        detalleLibroPortada.innerHTML = bloquePortada(libro);
        detalleLibroTitulo.textContent = `${libro.titulo} - ${libro.autor}`;
        detalleLibroMeta.textContent = `Genero: ${libro.genero || "Sin genero"}`;
        detalleLibroPropietario.textContent = `Propietario: ${libro.propietario || "No disponible"}`;
        const descripcion = soloDescripcionEspanol(libro.descripcion || "");
        detalleLibroDescripcion.textContent = descripcion || "Sin descripcion disponible.";
        botonSolicitarDetalle.hidden = false;
        botonSolicitarDetalle.textContent = "Solicitar prestamo";
        botonSolicitarDetalle.dataset.modo = "catalogo";
        botonSolicitarDetalle.dataset.idLibro = String(libro.id);
        botonSolicitarDetalle.dataset.idPrestamo = "";
        modalDetalleLibro.hidden = false;
        document.body.classList.add("modal-abierto");
    }

    function abrirDetallePrestamo(idPrestamo) {
        const prestamo = prestamosPorId.get(idPrestamo);
        if (!prestamo) {
            mostrarMensaje("No se encontro la ficha del prestamo seleccionado.", "error");
            return;
        }

        const activo = prestamo.fecha_devolucion === null;
        detalleLibroPortada.innerHTML = bloquePortada(prestamo);
        detalleLibroTitulo.textContent = `${prestamo.titulo} - ${prestamo.autor}`;
        detalleLibroMeta.textContent = `Genero: ${prestamo.genero || "Sin genero"}`;
        detalleLibroPropietario.textContent = `Dueno: ${prestamo.nombre_dueno || "No disponible"} | Prestado: ${prestamo.fecha_prestamo || "-"}`;
        const descripcion = soloDescripcionEspanol(prestamo.descripcion || "");
        detalleLibroDescripcion.textContent = descripcion || "Sin descripcion disponible.";
        botonSolicitarDetalle.dataset.modo = "prestamo";
        botonSolicitarDetalle.dataset.idLibro = "";
        botonSolicitarDetalle.dataset.idPrestamo = String(prestamo.id);
        botonSolicitarDetalle.hidden = !activo;
        botonSolicitarDetalle.textContent = "Devolver prestamo";
        modalDetalleLibro.hidden = false;
        document.body.classList.add("modal-abierto");
    }

    function cerrarFichaLibro() {
        modalDetalleLibro.hidden = true;
        document.body.classList.remove("modal-abierto");
        botonSolicitarDetalle.hidden = false;
        botonSolicitarDetalle.textContent = "Solicitar prestamo";
        botonSolicitarDetalle.dataset.modo = "catalogo";
        botonSolicitarDetalle.dataset.idLibro = "";
        botonSolicitarDetalle.dataset.idPrestamo = "";
    }

    async function solicitarPrestamoDesdeCatalogo(idLibro) {
        if (Number.isNaN(idLibro) || idLibro <= 0) {
            throw new Error("El libro seleccionado no es valido.");
        }

        await window.fabularia.llamarApi("/prestamos", "POST", { id_libro: idLibro });
        await cargarPanelCompleto();
        activarTab("prestamos");
    }

    function renderizarLibrosDisponibles(libros) {
        listaLibrosDisponibles.innerHTML = "";
        librosCatalogoPorId.clear();

        if (!libros.length) {
            estadoResultadosCatalogo.textContent = "No hay resultados para tu busqueda actual.";
            listaLibrosDisponibles.innerHTML = "<li class='catalogo-vacio'>No hay libros disponibles con esos filtros.</li>";
            return;
        }

        estadoResultadosCatalogo.textContent = `Se encontraron ${libros.length} libro(s) disponibles.`;

        for (const libro of libros) {
            const idLibro = Number(libro.id);
            librosCatalogoPorId.set(idLibro, libro);

            const elemento = document.createElement("li");
            elemento.className = "catalogo-card";
            const resumen = recortarTexto(libro.descripcion || "", 170);

            elemento.innerHTML = `
                <div class="catalogo-card-portada">
                    <button type="button" class="catalogo-clickable" data-id-libro-detalle="${idLibro}" aria-label="Ver detalle de ${escaparHtml(libro.titulo)}">
                        ${bloquePortada(libro)}
                    </button>
                </div>
                <div class="catalogo-card-cuerpo">
                    <button type="button" class="catalogo-titulo-btn" data-id-libro-detalle="${idLibro}">
                        ${escaparHtml(libro.titulo)}
                    </button>
                    <p class="pequeno">Autor: ${escaparHtml(libro.autor)}</p>
                    <p class="pequeno">Genero: ${escaparHtml(libro.genero)}</p>
                    <p class="pequeno">Propietario: ${escaparHtml(libro.propietario)}</p>
                    <p class="catalogo-descripcion">${escaparHtml(resumen || "Sin descripcion disponible.")}</p>
                    <div class="catalogo-card-acciones">
                        <button type="button" data-id-libro="${idLibro}">Solicitar</button>
                        <button type="button" class="boton-secundario" data-id-libro-detalle="${idLibro}">Ver ficha</button>
                    </div>
                </div>
            `;
            listaLibrosDisponibles.appendChild(elemento);
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
            const disponible = Number(libro.disponible) === 1;
            const estado = disponible ? "Disponible" : "Prestado";
            const accion = disponible
                ? `<button type="button" class="boton-eliminar-mini" data-id-libro-eliminar="${libro.id}" aria-label="Eliminar libro" title="Eliminar libro">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h10l-1 11a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2L7 9Zm3 2v8h2v-8h-2Zm4 0v8h2v-8h-2Z" fill="currentColor"/>
                        </svg>
                    </button>`
                : `<span class="pequeno">No se puede eliminar: prestamo activo.</span>`;
            const elemento = document.createElement("li");
            elemento.innerHTML = `
                <div class="libro-item">
                    ${bloquePortada(libro)}
                    <div>
                        <strong>${escaparHtml(libro.titulo)} - ${escaparHtml(libro.autor)}</strong>
                        <p class="pequeno">Genero: ${escaparHtml(libro.genero)}</p>
                        <p class="pequeno">Estado: ${estado}</p>
                    </div>
                    <div class="libro-accion">${accion}</div>
                </div>
            `;
            lista.appendChild(elemento);
        }
    }

    function renderizarMisPrestamos(prestamos) {
        const lista = document.getElementById("listaMisPrestamos");
        lista.innerHTML = "";
        prestamosPorId.clear();

        if (!prestamos.length) {
            lista.innerHTML = "<li>No tienes prestamos registrados.</li>";
            return;
        }

        for (const prestamo of prestamos) {
            prestamosPorId.set(Number(prestamo.id), prestamo);
            const activo = prestamo.fecha_devolucion === null;
            const botonDevolver = activo
                ? `<button type="button" data-id-prestamo="${prestamo.id}">Devolver</button>`
                : `<span class="pequeno">Devuelto: ${escaparHtml(prestamo.fecha_devolucion)}</span>`;

            const elemento = document.createElement("li");
            elemento.innerHTML = `
                <div class="libro-item">
                    ${bloquePortada(prestamo)}
                    <div>
                        <strong>${escaparHtml(prestamo.titulo)} - ${escaparHtml(prestamo.autor)}</strong>
                        <p class="pequeno">Genero: ${escaparHtml(prestamo.genero || "General")}</p>
                        <p class="pequeno">Dueno: ${escaparHtml(prestamo.nombre_dueno)}</p>
                        <p class="pequeno">Prestado: ${escaparHtml(prestamo.fecha_prestamo)}</p>
                    </div>
                    <div class="libro-accion libro-accion--prestamo">
                        <button type="button" class="boton-secundario" data-id-prestamo-detalle="${prestamo.id}">Ver ficha</button>
                        ${botonDevolver}
                    </div>
                </div>
            `;
            lista.appendChild(elemento);
        }
    }

    async function cargarLibrosDisponibles(termino = "", genero = "") {
        const parametros = new URLSearchParams();
        if (termino) parametros.set("buscar", termino);
        if (genero) parametros.set("genero", genero);
        const consulta = parametros.toString() ? `?${parametros.toString()}` : "";
        estadoResultadosCatalogo.textContent = "Cargando resultados...";
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
        telefonoUsuarioAjustes.value = usuario.telefono || "";
        pintarEstadoTelegram(usuario);

        return usuario;
    }

    async function cargarPanelCompleto() {
        await Promise.all([cargarLibrosDisponibles(), cargarMisLibros(), cargarMisPrestamos()]);
    }

    async function buscarEnCatalogoGlobal() {
        const texto = inputBusquedaCatalogo.value.trim();
        if (texto.length < 2) {
            limpiarSugerenciasCatalogo();
            return;
        }

        try {
            const datos = await window.fabularia.llamarApi(`/catalogo/sugerencias?texto=${encodeURIComponent(texto)}`);
            renderizarSugerenciasCatalogo(datos.sugerencias || []);
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    }

    async function cerrarSesion() {
        await window.fabularia.llamarApi("/usuarios/logout", "POST");
        window.location.href = <?= json_encode($urlLogin, JSON_UNESCAPED_SLASHES) ?>;
    }

    document.querySelectorAll(".tab-btn").forEach((boton) => {
        boton.addEventListener("click", () => activarTab(boton.dataset.tab));
    });

    document.getElementById("botonBuscarCatalogoGlobal").addEventListener("click", async () => {
        await buscarEnCatalogoGlobal();
    });

    let temporizadorBusquedaCatalogo = null;
    inputBusquedaCatalogo.addEventListener("input", () => {
        if (temporizadorBusquedaCatalogo !== null) {
            clearTimeout(temporizadorBusquedaCatalogo);
        }
        temporizadorBusquedaCatalogo = setTimeout(() => {
            buscarEnCatalogoGlobal();
        }, 350);
    });

    document.getElementById("formularioLibro").addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarMensaje();
        const formulario = evento.currentTarget;
        const datos = {
            titulo: formulario.titulo.value,
            autor: formulario.autor.value,
            genero: formulario.genero.value,
            portada_url: formulario.portada_url.value.trim(),
            descripcion: formulario.descripcion.value
        };

        try {
            await window.fabularia.llamarApi("/libros", "POST", datos);
            formulario.reset();
            mostrarMensaje(
                "Libro guardado correctamente. Aviso: si se presta, no podras eliminarlo hasta que finalice el prestamo.",
                "ok"
            );
            activarTab("mis-libros");
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

    listaLibrosDisponibles.addEventListener("click", async (evento) => {
        const botonDetalle = evento.target.closest("button[data-id-libro-detalle]");
        if (botonDetalle) {
            const idLibroDetalle = Number(botonDetalle.dataset.idLibroDetalle);
            abrirDetalleLibro(idLibroDetalle);
            return;
        }

        const botonSolicitar = evento.target.closest("button[data-id-libro]");
        if (!botonSolicitar) return;

        limpiarMensaje();
        try {
            await solicitarPrestamoDesdeCatalogo(Number(botonSolicitar.dataset.idLibro));
            cerrarFichaLibro();
            mostrarMensaje("Prestamo solicitado correctamente.", "ok");
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    cerrarDetalleLibro.addEventListener("click", () => {
        cerrarFichaLibro();
    });

    modalDetalleLibro.addEventListener("click", (evento) => {
        if (evento.target === modalDetalleLibro) {
            cerrarFichaLibro();
        }
    });

    document.addEventListener("keydown", (evento) => {
        if (evento.key === "Escape" && !modalDetalleLibro.hidden) {
            cerrarFichaLibro();
        }
    });

    botonSolicitarDetalle.addEventListener("click", async () => {
        const modo = botonSolicitarDetalle.dataset.modo || "catalogo";
        const idLibro = Number(botonSolicitarDetalle.dataset.idLibro || "0");
        const idPrestamo = Number(botonSolicitarDetalle.dataset.idPrestamo || "0");

        limpiarMensaje();
        try {
            if (modo === "prestamo") {
                if (Number.isNaN(idPrestamo) || idPrestamo <= 0) {
                    throw new Error("No se pudo identificar el prestamo.");
                }
                await window.fabularia.llamarApi("/prestamos/devolver", "POST", { id_prestamo: idPrestamo });
                await cargarPanelCompleto();
                activarTab("prestamos");
                mostrarMensaje("Prestamo devuelto correctamente.", "ok");
                cerrarFichaLibro();
                return;
            }

            await solicitarPrestamoDesdeCatalogo(idLibro);
            cerrarFichaLibro();
            mostrarMensaje("Prestamo solicitado correctamente.", "ok");
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("listaMisPrestamos").addEventListener("click", async (evento) => {
        const botonDetalle = evento.target.closest("button[data-id-prestamo-detalle]");
        if (botonDetalle) {
            abrirDetallePrestamo(Number(botonDetalle.dataset.idPrestamoDetalle));
            return;
        }

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

    document.getElementById("listaMisLibros").addEventListener("click", async (evento) => {
        const boton = evento.target.closest("button[data-id-libro-eliminar]");
        if (!boton) return;

        const idLibro = Number(boton.dataset.idLibroEliminar);
        if (Number.isNaN(idLibro) || idLibro <= 0) {
            mostrarMensaje("No se pudo identificar el libro a eliminar.", "error");
            return;
        }

        if (!window.confirm("Vas a eliminar este libro del intercambio. Deseas continuar?")) {
            return;
        }

        limpiarMensaje();
        try {
            await window.fabularia.llamarApi("/libros", "DELETE", { id_libro: idLibro });
            mostrarMensaje("Libro eliminado correctamente.", "ok");
            await cargarPanelCompleto();
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });
document.getElementById("botonCerrarSesionRapido").addEventListener("click", async () => {
        limpiarMensaje();
        try {
            await cerrarSesion();
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    botonDesvincularTelegram.addEventListener("click", async () => {
        limpiarMensaje();

        if (botonDesvincularTelegram.disabled) {
            mostrarMensaje("No hay una cuenta Telegram vinculada.", "error");
            return;
        }

        try {
            await window.fabularia.llamarApi("/usuarios/telegram/desvincular", "POST");
            await actualizarSesion();
            mostrarMensaje("Telegram desvinculado correctamente.", "ok");
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("formularioCambiarContrasena").addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarMensaje();

        const formulario = evento.currentTarget;
        const datos = {
            contrasena_actual: formulario.contrasena_actual.value,
            contrasena_nueva: formulario.contrasena_nueva.value,
            confirmar_contrasena: formulario.confirmar_contrasena.value
        };

        try {
            await window.fabularia.llamarApi("/usuarios/cambiar-contrasena", "POST", datos);
            formulario.reset();
            mostrarMensaje("Contraseña actualizada correctamente.", "ok");
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("formularioTelefono").addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarMensaje();

        const formulario = evento.currentTarget;
        const telefono = formulario.telefono.value.trim();

        try {
            await window.fabularia.llamarApi("/usuarios/telefono", "POST", { telefono });
            await actualizarSesion();
            mostrarMensaje("Teléfono actualizado correctamente.", "ok");
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    document.getElementById("formularioEliminarCuenta").addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarMensaje();

        const formulario = evento.currentTarget;
        const contrasena = formulario.contrasena.value;
        const confirmado = formulario.confirmacion.checked;

        if (!confirmado) {
            mostrarMensaje("Debes confirmar la eliminacion de cuenta.", "error");
            return;
        }

        if (!window.confirm("Esta accion elimina tu cuenta y no se puede deshacer. Continuar?")) {
            return;
        }

        try {
            await window.fabularia.llamarApi("/usuarios/cuenta", "DELETE", { contrasena });
            window.location.href = <?= json_encode($urlLogin, JSON_UNESCAPED_SLASHES) ?>;
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    (async () => {
        try {
            await actualizarSesion();
            await cargarPanelCompleto();
            activarTab("catalogo");
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    })();
</script>
</body>
</html>

