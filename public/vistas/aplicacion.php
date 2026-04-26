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
                <button type="button" class="tab-btn tab-btn--activa" data-tab="catalogo">Cat&aacute;logo</button>
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
                    <h3>Cat&aacute;logo</h3>
                    <div class="catalogo-vistas-nav" role="tablist" aria-label="Tipo de catalogo">
                        <button id="botonVistaCatalogoUsuarios" type="button" class="catalogo-vista-btn catalogo-vista-btn--activa" data-catalogo-vista="usuarios">Libros de usuarios</button>
                        <button id="botonVistaCatalogoLibres" type="button" class="catalogo-vista-btn" data-catalogo-vista="libres">Libros gratuitos</button>
                    </div>

                    <section id="vistaCatalogoUsuarios" class="catalogo-vista">
                        <form id="formularioBusquedaUsuarios" class="catalogo-filtros">
                            <div class="catalogo-filtro-grupo">
                                <label for="busquedaTextoUsuarios">T&iacute;tulo o autor</label>
                                <input id="busquedaTextoUsuarios" name="buscar" placeholder="Busca por titulo o autor...">
                            </div>

                            <div class="catalogo-filtro-grupo">
                                <label for="busquedaGeneroUsuarios">Genero</label>
                                <input id="busquedaGeneroUsuarios" name="genero" placeholder="Fantasia, novela, poesia...">
                            </div>

                            <button type="submit">Buscar</button>
                        </form>

                        <p id="estadoResultadosCatalogoUsuarios" class="pequeno catalogo-estado"></p>
                        <ul id="listaLibrosDisponibles" class="catalogo-grid"></ul>
                    </section>

                    <section id="vistaCatalogoLibres" class="catalogo-vista" hidden>
                        <form id="formularioBusquedaLibres" class="catalogo-filtros catalogo-filtros--libre">
                            <div class="catalogo-filtro-grupo">
                                <label for="busquedaTextoLibres">T&iacute;tulo o autor</label>
                                <input id="busquedaTextoLibres" name="texto" placeholder="Busca libros de dominio p&uacute;blico...">
                            </div>

                            <button type="submit">Buscar</button>
                        </form>

                        <p id="estadoResultadosCatalogoLibres" class="pequeno catalogo-estado"></p>
                        <div class="catalogo-libre-secciones">
                            <section class="catalogo-libre-seccion">
                                <h4 class="catalogo-subtitulo">Libros en espa&ntilde;ol</h4>
                                <ul id="listaLibrosLibresEs" class="catalogo-grid"></ul>
                                <div class="catalogo-libre-paginacion">
                                    <button id="botonLibresEsAnterior" type="button" class="boton-secundario">Anterior</button>
                                    <span id="paginacionLibresEsInfo" class="pequeno">P&aacute;gina 1</span>
                                    <button id="botonLibresEsSiguiente" type="button">Siguiente</button>
                                </div>
                            </section>

                            <section class="catalogo-libre-seccion">
                                <h4 class="catalogo-subtitulo">Libros en ingl&eacute;s</h4>
                                <ul id="listaLibrosLibresEn" class="catalogo-grid"></ul>
                                <div class="catalogo-libre-paginacion">
                                    <button id="botonLibresEnAnterior" type="button" class="boton-secundario">Anterior</button>
                                    <span id="paginacionLibresEnInfo" class="pequeno">P&aacute;gina 1</span>
                                    <button id="botonLibresEnSiguiente" type="button">Siguiente</button>
                                </div>
                            </section>
                        </div>
                    </section>
                </article>
            </section>

            <section class="tab-panel" data-panel="publicar">
                <article class="subcard subcard--compacta">
                    <h3>Publicar libro</h3>
                    <div class="catalogo-busqueda">
                        <label for="busquedaCatalogoGlobal">Buscar en cat&aacute;logo global</label>
                        <div class="catalogo-busqueda-fila">
                            <input id="busquedaCatalogoGlobal" type="text" placeholder="Escribe un t&iacute;tulo para sugerencias...">
                            <button id="botonBuscarCatalogoGlobal" type="button" class="boton-secundario">Buscar</button>
                        </div>
                        <ul id="listaSugerenciasCatalogo" class="lista lista-sugerencias"></ul>
                    </div>

                    <form id="formularioLibro">
                        <label for="libroTitulo">T&iacute;tulo</label>
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
                    <section id="bloqueContinuarLectura" class="subseccion-lectura" hidden>
                        <h4>Continuar lectura</h4>
                        <ul id="listaContinuarLectura" class="lista lista-continuar-lectura"></ul>
                    </section>
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

<div id="modalLectorLibro" class="modal-libro modal-lector" hidden>
    <div class="modal-lector-contenido" role="dialog" aria-modal="true" aria-labelledby="lectorTituloLibro">
        <button id="cerrarLectorLibro" type="button" class="modal-cerrar" aria-label="Cerrar lector">&times;</button>
        <div class="lector-cabecera">
            <h3 id="lectorTituloLibro"></h3>
            <p id="lectorMetaLibro" class="pequeno"></p>
        </div>
        <p id="lectorPaginaInfo" class="lector-pagina-info"></p>
        <div class="lector-progreso">
            <div class="lector-progreso-barra">
                <div id="lectorProgresoValor" class="lector-progreso-valor"></div>
            </div>
            <p id="lectorProgresoTexto" class="pequeno lector-progreso-texto">0% completado</p>
        </div>
        <div id="lectorTextoPagina" class="lector-texto" tabindex="0"></div>
        <div class="lector-controles">
            <button id="botonPaginaAnterior" type="button" class="boton-secundario">Pagina anterior</button>
            <button id="botonPaginaSiguiente" type="button">Pagina siguiente</button>
            <button id="botonGuardarProgresoLectura" type="button" class="boton-secundario">Guardar progreso</button>
        </div>
        <p class="pequeno lector-aviso">
            Modo lectura protegido: contenido no seleccionable, copia y menu contextual desactivados.
        </p>
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
    const botonesVistaCatalogo = document.querySelectorAll("[data-catalogo-vista]");
    const vistaCatalogoUsuarios = document.getElementById("vistaCatalogoUsuarios");
    const vistaCatalogoLibres = document.getElementById("vistaCatalogoLibres");
    const listaLibrosLibresEs = document.getElementById("listaLibrosLibresEs");
    const listaLibrosLibresEn = document.getElementById("listaLibrosLibresEn");
    const botonLibresEsAnterior = document.getElementById("botonLibresEsAnterior");
    const botonLibresEsSiguiente = document.getElementById("botonLibresEsSiguiente");
    const paginacionLibresEsInfo = document.getElementById("paginacionLibresEsInfo");
    const botonLibresEnAnterior = document.getElementById("botonLibresEnAnterior");
    const botonLibresEnSiguiente = document.getElementById("botonLibresEnSiguiente");
    const paginacionLibresEnInfo = document.getElementById("paginacionLibresEnInfo");
    const estadoResultadosCatalogoUsuarios = document.getElementById("estadoResultadosCatalogoUsuarios");
    const estadoResultadosCatalogoLibres = document.getElementById("estadoResultadosCatalogoLibres");
    const listaLibrosDisponibles = document.getElementById("listaLibrosDisponibles");
    const bloqueContinuarLectura = document.getElementById("bloqueContinuarLectura");
    const listaContinuarLectura = document.getElementById("listaContinuarLectura");
    const modalDetalleLibro = document.getElementById("modalDetalleLibro");
    const cerrarDetalleLibro = document.getElementById("cerrarDetalleLibro");
    const detalleLibroPortada = document.getElementById("detalleLibroPortada");
    const detalleLibroTitulo = document.getElementById("detalleLibroTitulo");
    const detalleLibroMeta = document.getElementById("detalleLibroMeta");
    const detalleLibroPropietario = document.getElementById("detalleLibroPropietario");
    const detalleLibroDescripcion = document.getElementById("detalleLibroDescripcion");
    const botonSolicitarDetalle = document.getElementById("botonSolicitarDetalle");
    const modalLectorLibro = document.getElementById("modalLectorLibro");
    const cerrarLectorLibro = document.getElementById("cerrarLectorLibro");
    const lectorTituloLibro = document.getElementById("lectorTituloLibro");
    const lectorMetaLibro = document.getElementById("lectorMetaLibro");
    const lectorPaginaInfo = document.getElementById("lectorPaginaInfo");
    const lectorProgresoValor = document.getElementById("lectorProgresoValor");
    const lectorProgresoTexto = document.getElementById("lectorProgresoTexto");
    const lectorTextoPagina = document.getElementById("lectorTextoPagina");
    const botonPaginaAnterior = document.getElementById("botonPaginaAnterior");
    const botonPaginaSiguiente = document.getElementById("botonPaginaSiguiente");
    const botonGuardarProgresoLectura = document.getElementById("botonGuardarProgresoLectura");
    const librosCatalogoPorId = new Map();
    const librosCatalogoLibrePorId = new Map();
    const prestamosPorId = new Map();
    let temporizadorMensaje = null;
    let vistaCatalogoActiva = "usuarios";
    let paginaLectorActual = 1;
    let totalPaginasLector = 1;
    let idPrestamoLectorActual = 0;
    let modoLectorActual = "prestamo";
    let referenciaLecturaLibreActual = null;
    let cargandoPaginaLector = false;
    let prestamosLecturaActiva = [];
    let paginaLibresEsActual = 1;
    let paginaLibresEnActual = 1;
    let haySiguienteLibresEs = false;
    let haySiguienteLibresEn = false;
    let textoBusquedaLibresActual = "";
    let catalogoLibreInicializado = false;
    let animacionCargaCatalogoLibres = null;
    const cacheCatalogoLibre = new Map();

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

    function claveCacheCatalogoLibre(texto, paginaEs, paginaEn) {
        return `${String(texto || "").trim().toLowerCase()}|es:${paginaEs}|en:${paginaEn}`;
    }

    function iniciarAnimacionCargaCatalogoLibres(baseTexto = "Cargando cat\u00E1logo gratuito") {
        detenerAnimacionCargaCatalogoLibres();
        let paso = 1;
        estadoResultadosCatalogoLibres.textContent = `${baseTexto}.`;
        animacionCargaCatalogoLibres = setInterval(() => {
            paso = (paso % 3) + 1;
            estadoResultadosCatalogoLibres.textContent = `${baseTexto}${".".repeat(paso)}`;
        }, 340);
    }

    function detenerAnimacionCargaCatalogoLibres() {
        if (animacionCargaCatalogoLibres !== null) {
            clearInterval(animacionCargaCatalogoLibres);
            animacionCargaCatalogoLibres = null;
        }
    }

    function activarTab(nombreTab) {
        document.querySelectorAll(".tab-btn").forEach((boton) => {
            boton.classList.toggle("tab-btn--activa", boton.dataset.tab === nombreTab);
        });

        document.querySelectorAll(".tab-panel").forEach((panel) => {
            panel.classList.toggle("tab-panel--activo", panel.dataset.panel === nombreTab);
        });
    }

    function activarVistaCatalogo(nombreVista) {
        vistaCatalogoActiva = nombreVista === "libres" ? "libres" : "usuarios";
        botonesVistaCatalogo.forEach((boton) => {
            boton.classList.toggle("catalogo-vista-btn--activa", boton.dataset.catalogoVista === vistaCatalogoActiva);
        });

        const mostrarUsuarios = vistaCatalogoActiva === "usuarios";
        vistaCatalogoUsuarios.hidden = !mostrarUsuarios;
        vistaCatalogoLibres.hidden = mostrarUsuarios;

        if (!mostrarUsuarios && !catalogoLibreInicializado) {
            void cargarLibrosCatalogoLibre(textoBusquedaLibresActual, { reiniciarPaginacion: true }).catch((error) => {
                mostrarMensaje(error.message, "error");
            });
        }
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

    function limpiarDescripcionCatalogoLibre(texto, idioma) {
        const contenido = String(texto ?? "").trim();
        if (!contenido) {
            return "";
        }

        if (idioma === "es") {
            return soloDescripcionEspanol(contenido);
        }

        return contenido;
    }

    function recortarDescripcionCatalogoLibre(texto, idioma, maximoCaracteres = 170) {
        const limpio = limpiarDescripcionCatalogoLibre(texto, idioma);
        if (limpio.length <= maximoCaracteres) {
            return limpio;
        }

        return `${limpio.slice(0, maximoCaracteres).trimEnd()}...`;
    }

    function obtenerClaveProgresoLibre(idExterno) {
        return `fabularia_lectura_libre_${String(idExterno || "").trim()}`;
    }

    function obtenerClaveIndiceLecturasLibres() {
        return "fabularia_lecturas_libres_indice";
    }

    function cargarProgresoLibreGuardado(idExterno) {
        const clave = obtenerClaveProgresoLibre(idExterno);
        const bruto = localStorage.getItem(clave);
        if (!bruto) {
            return { pagina_actual: 1, total_paginas: 0 };
        }

        try {
            const datos = JSON.parse(bruto);
            return {
                pagina_actual: Math.max(1, Number(datos.pagina_actual) || 1),
                total_paginas: Math.max(0, Number(datos.total_paginas) || 0),
            };
        } catch {
            return { pagina_actual: 1, total_paginas: 0 };
        }
    }

    function cargarIndiceLecturasLibres() {
        const bruto = localStorage.getItem(obtenerClaveIndiceLecturasLibres());
        if (!bruto) {
            return {};
        }

        try {
            const datos = JSON.parse(bruto);
            return datos && typeof datos === "object" ? datos : {};
        } catch {
            return {};
        }
    }

    function guardarIndiceLecturasLibres(indice) {
        localStorage.setItem(obtenerClaveIndiceLecturasLibres(), JSON.stringify(indice));
    }

    function registrarLecturaLibre(libro, paginaActual, totalPaginas) {
        if (!libro || !libro.id_externo) {
            return;
        }

        const idExterno = String(libro.id_externo).trim();
        if (!idExterno) {
            return;
        }

        const indice = cargarIndiceLecturasLibres();
        indice[idExterno] = {
            id_externo: idExterno,
            titulo: String(libro.titulo || "Libro gratuito"),
            autor: String(libro.autor || "Autor desconocido"),
            portada_url: String(libro.portada_url || ""),
            idioma: String(libro.idioma || "es").toLowerCase() === "en" ? "en" : "es",
            pagina_actual: Math.max(1, Number(paginaActual) || 1),
            total_paginas: Math.max(1, Number(totalPaginas) || 1),
            actualizado_en: Date.now(),
        };
        guardarIndiceLecturasLibres(indice);
    }

    function obtenerLecturasLibresContinuar() {
        const indice = cargarIndiceLecturasLibres();
        return Object.values(indice)
            .filter((item) => item && item.id_externo)
            .map((item) => ({
                id_externo: String(item.id_externo),
                titulo: String(item.titulo || "Libro gratuito"),
                autor: String(item.autor || "Autor desconocido"),
                portada_url: String(item.portada_url || ""),
                idioma: String(item.idioma || "es"),
                pagina_actual: Math.max(1, Number(item.pagina_actual) || 1),
                total_paginas: Math.max(1, Number(item.total_paginas) || 1),
                actualizado_en: Number(item.actualizado_en) || 0,
            }))
            .sort((a, b) => b.actualizado_en - a.actualizado_en);
    }

    function guardarProgresoLibreGuardado(idExterno, paginaActual, totalPaginas, libro = null) {
        const clave = obtenerClaveProgresoLibre(idExterno);
        localStorage.setItem(clave, JSON.stringify({
            pagina_actual: Math.max(1, Number(paginaActual) || 1),
            total_paginas: Math.max(1, Number(totalPaginas) || 1),
        }));

        if (libro && libro.id_externo) {
            registrarLecturaLibre(libro, paginaActual, totalPaginas);
            return;
        }

        const indice = cargarIndiceLecturasLibres();
        const id = String(idExterno || "").trim();
        if (!id || !indice[id]) {
            return;
        }

        indice[id].pagina_actual = Math.max(1, Number(paginaActual) || 1);
        indice[id].total_paginas = Math.max(1, Number(totalPaginas) || 1);
        indice[id].actualizado_en = Date.now();
        guardarIndiceLecturasLibres(indice);
    }

    function pintarProgresoLector(porcentaje) {
        const valor = Math.max(0, Math.min(100, Number(porcentaje) || 0));
        lectorProgresoValor.style.width = `${valor}%`;
        lectorProgresoTexto.textContent = `${valor.toFixed(0)}% completado`;
    }

    function pintarErrorLector(mensaje) {
        lectorTextoPagina.textContent = String(mensaje || "No se pudo cargar el contenido del libro.");
        lectorPaginaInfo.textContent = "Lectura no disponible";
        pintarProgresoLector(0);
        botonPaginaAnterior.disabled = true;
        botonPaginaSiguiente.disabled = true;
    }

    function pareceTituloLectura(texto) {
        const linea = String(texto || "").trim();
        if (!linea || linea.length > 120) {
            return false;
        }

        const normalizada = linea.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        if (/^(capitulo|cap\.|chapter|libro|parte|prologo|epilogo|acto)\b/i.test(normalizada)) {
            return true;
        }

        const limpia = normalizada.replace(/[^A-Za-z0-9 ]/g, "");
        if (!limpia) {
            return false;
        }

        const mayusculas = limpia.replace(/[^A-Z]/g, "").length;
        const letras = limpia.replace(/[^A-Za-z]/g, "").length;
        if (letras < 6) {
            return false;
        }

        return (mayusculas / letras) >= 0.68;
    }

    function formatearContenidoLectura(contenido) {
        const textoPlano = String(contenido || "").replace(/\r/g, "").trim();
        if (!textoPlano) {
            return "<p>Sin contenido disponible para esta pagina.</p>";
        }

        const bloques = textoPlano
            .split(/\n{2,}/)
            .map((bloque) => bloque.trim())
            .filter((bloque) => bloque !== "");

        if (bloques.length === 0) {
            return "<p>Sin contenido disponible para esta pagina.</p>";
        }

        return bloques.map((bloque) => {
            const seguro = escaparHtml(bloque).replace(/\n/g, "<br>");
            if (pareceTituloLectura(bloque)) {
                return `<h4 class="lector-bloque-titulo">${seguro}</h4>`;
            }

            return `<p class="lector-bloque-parrafo">${seguro}</p>`;
        }).join("");
    }

    async function cargarPaginaLector(paginaObjetivo) {
        if (cargandoPaginaLector) {
            return;
        }

        if (modoLectorActual === "prestamo" && idPrestamoLectorActual <= 0) {
            return;
        }

        if (modoLectorActual === "libre" && (!referenciaLecturaLibreActual || !referenciaLecturaLibreActual.id_externo)) {
            return;
        }

        const pagina = Math.max(1, Number(paginaObjetivo) || 1);
        cargandoPaginaLector = true;
        botonPaginaAnterior.disabled = true;
        botonPaginaSiguiente.disabled = true;

        try {
            let respuesta;
            if (modoLectorActual === "libre") {
                respuesta = await window.fabularia.llamarApi(
                    `/catalogo/libre/lectura?id_externo=${encodeURIComponent(referenciaLecturaLibreActual.id_externo)}&pagina=${encodeURIComponent(pagina)}`
                );
            } else {
                respuesta = await window.fabularia.llamarApi(
                    `/prestamos/lectura?id_prestamo=${encodeURIComponent(idPrestamoLectorActual)}&pagina=${encodeURIComponent(pagina)}`
                );
            }

            const lectura = respuesta.lectura || {};

            paginaLectorActual = Math.max(1, Number(lectura.pagina_actual) || 1);
            totalPaginasLector = Math.max(1, Number(lectura.total_paginas) || 1);
            lectorTextoPagina.innerHTML = formatearContenidoLectura(lectura.contenido || "");
            lectorPaginaInfo.textContent = `Pagina ${paginaLectorActual} de ${totalPaginasLector}`;
            pintarProgresoLector(Number(lectura.porcentaje_progreso) || 0);

            botonPaginaAnterior.disabled = paginaLectorActual <= 1;
            botonPaginaSiguiente.disabled = paginaLectorActual >= totalPaginasLector;
            botonGuardarProgresoLectura.disabled = false;

            if (modoLectorActual === "prestamo") {
                const prestamo = prestamosPorId.get(idPrestamoLectorActual);
                if (prestamo) {
                    prestamo.pagina_lectura_actual = paginaLectorActual;
                    prestamo.paginas_lectura_totales = totalPaginasLector;
                }
            } else if (referenciaLecturaLibreActual) {
                guardarProgresoLibreGuardado(
                    referenciaLecturaLibreActual.id_externo,
                    paginaLectorActual,
                    totalPaginasLector,
                    referenciaLecturaLibreActual
                );
            }
        } catch (error) {
            botonGuardarProgresoLectura.disabled = true;
            pintarErrorLector(error?.message || "No se pudo cargar el contenido de lectura.");
            throw error;
        } finally {
            cargandoPaginaLector = false;
        }
    }

    async function abrirLectorPrestamo(idPrestamo) {
        const prestamo = prestamosPorId.get(idPrestamo);
        if (!prestamo) {
            throw new Error("No se encontro el prestamo para abrir el lector.");
        }

        if (prestamo.fecha_devolucion !== null) {
            throw new Error("Este prestamo ya fue devuelto y no se puede abrir en modo lectura.");
        }

        if (Number(prestamo.lectura_publica) !== 1) {
            throw new Error("Este libro no tiene lectura publica disponible.");
        }

        modoLectorActual = "prestamo";
        referenciaLecturaLibreActual = null;
        idPrestamoLectorActual = idPrestamo;
        lectorTituloLibro.textContent = `${prestamo.titulo} - ${prestamo.autor}`;
        lectorMetaLibro.textContent = `Dueno: ${prestamo.nombre_dueno || "No disponible"} | Prestado: ${prestamo.fecha_prestamo || "-"}`;
        lectorTextoPagina.textContent = "Cargando contenido...";
        lectorPaginaInfo.textContent = "";
        botonGuardarProgresoLectura.disabled = true;
        pintarProgresoLector(0);

        modalLectorLibro.hidden = false;
        document.body.classList.add("modal-abierto");

        const paginaInicial = Math.max(1, Number(prestamo.pagina_lectura_actual) || 1);
        await cargarPaginaLector(paginaInicial);
        lectorTextoPagina.focus();
    }

    async function abrirLectorLibroLibre(libro) {
        if (!libro || !libro.id_externo) {
            throw new Error("No se encontro el libro gratuito para abrir el lector.");
        }

        modoLectorActual = "libre";
        idPrestamoLectorActual = 0;
        referenciaLecturaLibreActual = {
            id_externo: String(libro.id_externo),
            titulo: String(libro.titulo || ""),
            autor: String(libro.autor || ""),
            idioma: String(libro.idioma || "es"),
            portada_url: String(libro.portada_url || ""),
        };

        lectorTituloLibro.textContent = `${libro.titulo} - ${libro.autor}`;
        const etiquetaIdioma = String(libro.idioma || "").toLowerCase() === "en" ? "EN" : "ES";
        lectorMetaLibro.textContent = `Catalogo gratuito | Idioma: ${etiquetaIdioma}`;
        lectorTextoPagina.textContent = "Cargando contenido...";
        lectorPaginaInfo.textContent = "";
        botonGuardarProgresoLectura.disabled = true;
        pintarProgresoLector(0);

        modalLectorLibro.hidden = false;
        document.body.classList.add("modal-abierto");

        const progresoGuardado = cargarProgresoLibreGuardado(libro.id_externo);
        const paginaInicial = Math.max(1, Number(progresoGuardado.pagina_actual) || 1);
        registrarLecturaLibre(referenciaLecturaLibreActual, paginaInicial, Math.max(1, Number(progresoGuardado.total_paginas) || 1));
        await cargarPaginaLector(paginaInicial);
        lectorTextoPagina.focus();
    }

    function cerrarLector() {
        modalLectorLibro.hidden = true;
        paginaLectorActual = 1;
        totalPaginasLector = 1;
        idPrestamoLectorActual = 0;
        modoLectorActual = "prestamo";
        referenciaLecturaLibreActual = null;
        lectorTituloLibro.textContent = "";
        lectorMetaLibro.textContent = "";
        lectorPaginaInfo.textContent = "";
        lectorTextoPagina.textContent = "";
        botonGuardarProgresoLectura.disabled = false;
        pintarProgresoLector(0);

        if (modalDetalleLibro.hidden) {
            document.body.classList.remove("modal-abierto");
        }
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
        botonSolicitarDetalle.dataset.idExterno = "";
        modalDetalleLibro.hidden = false;
        document.body.classList.add("modal-abierto");
    }

    function abrirDetalleLibroLibre(idExterno) {
        const libro = librosCatalogoLibrePorId.get(String(idExterno));
        if (!libro) {
            mostrarMensaje("No se encontro la ficha del libro gratuito.", "error");
            return;
        }

        detalleLibroPortada.innerHTML = bloquePortada(libro);
        detalleLibroTitulo.textContent = `${libro.titulo} - ${libro.autor}`;
        const idioma = String(libro.idioma || "es").toLowerCase();
        const etiquetaIdioma = idioma === "en" ? "EN" : "ES";
        detalleLibroMeta.textContent = `Género: Dominio público | Idioma: ${etiquetaIdioma}`;
        detalleLibroPropietario.textContent = "Catálogo gratuito (sin préstamo entre usuarios).";
        const descripcion = limpiarDescripcionCatalogoLibre(libro.descripcion || "", idioma);
        detalleLibroDescripcion.textContent = descripcion || "Sin descripcion disponible.";
        botonSolicitarDetalle.hidden = false;
        botonSolicitarDetalle.textContent = "Leer gratis";
        botonSolicitarDetalle.dataset.modo = "catalogo-libre";
        botonSolicitarDetalle.dataset.idLibro = "";
        botonSolicitarDetalle.dataset.idPrestamo = "";
        botonSolicitarDetalle.dataset.idExterno = String(libro.id_externo);
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
        botonSolicitarDetalle.dataset.idExterno = "";
        botonSolicitarDetalle.hidden = !activo;
        botonSolicitarDetalle.textContent = "Devolver prestamo";
        modalDetalleLibro.hidden = false;
        document.body.classList.add("modal-abierto");
    }

    function cerrarFichaLibro() {
        modalDetalleLibro.hidden = true;
        if (modalLectorLibro.hidden) {
            document.body.classList.remove("modal-abierto");
        }
        botonSolicitarDetalle.hidden = false;
        botonSolicitarDetalle.textContent = "Solicitar prestamo";
        botonSolicitarDetalle.dataset.modo = "catalogo";
        botonSolicitarDetalle.dataset.idLibro = "";
        botonSolicitarDetalle.dataset.idPrestamo = "";
        botonSolicitarDetalle.dataset.idExterno = "";
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
            estadoResultadosCatalogoUsuarios.textContent = "No hay resultados para tu busqueda actual.";
            listaLibrosDisponibles.innerHTML = "<li class='catalogo-vacio'>No hay libros disponibles con esos filtros.</li>";
            return;
        }

        estadoResultadosCatalogoUsuarios.textContent = `Se encontraron ${libros.length} libro(s) disponibles en intercambio de usuarios.`;

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

    function crearElementoLibroLibre(libro) {
        const idExterno = String(libro.id_externo || "");
        const elemento = document.createElement("li");
        elemento.className = "catalogo-card";

        const idioma = String(libro.idioma || "es").toLowerCase();
        const idiomaEtiqueta = idioma === "en" ? "EN" : "ES";
        const avisoIdioma = idioma === "en"
            ? `<span class="catalogo-idioma-badge catalogo-idioma-badge--en">EN (texto en inglés)</span>`
            : `<span class="catalogo-idioma-badge">ES</span>`;
        const resumen = recortarDescripcionCatalogoLibre(libro.descripcion || "", idioma, 170);

        elemento.innerHTML = `
            <div class="catalogo-card-portada">
                <button type="button" class="catalogo-clickable" data-id-libro-libre-detalle="${escaparHtml(idExterno)}" aria-label="Ver detalle de ${escaparHtml(libro.titulo)}">
                    ${bloquePortada(libro)}
                </button>
            </div>
            <div class="catalogo-card-cuerpo">
                <button type="button" class="catalogo-titulo-btn" data-id-libro-libre-detalle="${escaparHtml(idExterno)}">
                    ${escaparHtml(libro.titulo)}
                </button>
                <p class="pequeno">Autor: ${escaparHtml(libro.autor)}</p>
                <p class="pequeno">Género: Dominio público</p>
                <p class="pequeno">Idioma: ${idiomaEtiqueta} ${avisoIdioma}</p>
                <p class="catalogo-descripcion">${escaparHtml(resumen || "Sin descripcion disponible.")}</p>
                <div class="catalogo-card-acciones">
                    <button type="button" data-id-libro-libre-leer="${escaparHtml(idExterno)}">Leer gratis</button>
                    <button type="button" class="boton-secundario" data-id-libro-libre-detalle="${escaparHtml(idExterno)}">Ver ficha</button>
                </div>
            </div>
        `;

        return elemento;
    }

    function actualizarPaginacionCatalogoLibre() {
        paginacionLibresEsInfo.textContent = `Página ${paginaLibresEsActual}`;
        paginacionLibresEnInfo.textContent = `Página ${paginaLibresEnActual}`;
        botonLibresEsAnterior.disabled = paginaLibresEsActual <= 1;
        botonLibresEnAnterior.disabled = paginaLibresEnActual <= 1;
        botonLibresEsSiguiente.disabled = !haySiguienteLibresEs;
        botonLibresEnSiguiente.disabled = !haySiguienteLibresEn;
    }

    function renderizarLibrosLibres(datosCatalogo) {
        listaLibrosLibresEs.innerHTML = "";
        listaLibrosLibresEn.innerHTML = "";
        librosCatalogoLibrePorId.clear();

        const datos = (datosCatalogo && typeof datosCatalogo === "object") ? datosCatalogo : {};
        let librosEs = Array.isArray(datos.libros_es) ? datos.libros_es : [];
        let librosEn = Array.isArray(datos.libros_en) ? datos.libros_en : [];

        if (librosEs.length === 0 && librosEn.length === 0) {
            const listaPlana = Array.isArray(datosCatalogo)
                ? datosCatalogo
                : (Array.isArray(datos.libros) ? datos.libros : []);

            if (listaPlana.length > 0) {
                for (const libro of listaPlana) {
                    const idioma = String(libro.idioma || "es").toLowerCase();
                    if (idioma === "en") {
                        librosEn.push(libro);
                    } else {
                        librosEs.push(libro);
                    }
                }
            }
        }

        const paginacion = (datos.paginacion && typeof datos.paginacion === "object") ? datos.paginacion : {};
        const paginacionEs = (paginacion.es && typeof paginacion.es === "object") ? paginacion.es : {};
        const paginacionEn = (paginacion.en && typeof paginacion.en === "object") ? paginacion.en : {};
        paginaLibresEsActual = Math.max(1, Number(paginacionEs.pagina_actual) || paginaLibresEsActual);
        paginaLibresEnActual = Math.max(1, Number(paginacionEn.pagina_actual) || paginaLibresEnActual);
        haySiguienteLibresEs = Boolean(paginacionEs.hay_siguiente);
        haySiguienteLibresEn = Boolean(paginacionEn.hay_siguiente);

        if (librosEs.length === 0 && librosEn.length === 0) {
            estadoResultadosCatalogoLibres.textContent = "No hay resultados en el catalogo gratuito con ese texto.";
            listaLibrosLibresEs.innerHTML = "<li class='catalogo-vacio'>No hay libros en español para ese filtro.</li>";
            listaLibrosLibresEn.innerHTML = "<li class='catalogo-vacio'>No hay libros en inglés para ese filtro.</li>";
            actualizarPaginacionCatalogoLibre();
            return;
        }

        for (const libro of [...librosEs, ...librosEn]) {
            const idExterno = String(libro.id_externo || "");
            if (!idExterno) {
                continue;
            }
            librosCatalogoLibrePorId.set(idExterno, libro);
        }

        estadoResultadosCatalogoLibres.textContent = `Mostrando ${librosEs.length} libro(s) en español (página ${paginaLibresEsActual}) y ${librosEn.length} en inglés (página ${paginaLibresEnActual}).`;

        if (librosEs.length === 0) {
            listaLibrosLibresEs.innerHTML = "<li class='catalogo-vacio'>No hay libros en español para ese filtro.</li>";
        } else {
            for (const libro of librosEs) {
                listaLibrosLibresEs.appendChild(crearElementoLibroLibre(libro));
            }
        }

        if (librosEn.length === 0) {
            listaLibrosLibresEn.innerHTML = "<li class='catalogo-vacio'>No hay libros en inglés para ese filtro.</li>";
        } else {
            for (const libro of librosEn) {
                listaLibrosLibresEn.appendChild(crearElementoLibroLibre(libro));
            }
        }

        actualizarPaginacionCatalogoLibre();
    }
    function renderizarMisLibros(libros) {
        const lista = document.getElementById("listaMisLibros");
        lista.innerHTML = "";
        if (!libros.length) {
            lista.innerHTML = "<li>No has publicado libros todavia.</li>";
            renderizarContinuarLectura();
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

        renderizarContinuarLectura();
    }

    function renderizarContinuarLectura() {
        if (!bloqueContinuarLectura || !listaContinuarLectura) {
            return;
        }

        listaContinuarLectura.innerHTML = "";
        const prestamos = Array.isArray(prestamosLecturaActiva) ? prestamosLecturaActiva : [];
        const lecturasLibres = obtenerLecturasLibresContinuar();
        if (prestamos.length === 0 && lecturasLibres.length === 0) {
            bloqueContinuarLectura.hidden = true;
            return;
        }

        bloqueContinuarLectura.hidden = false;

        for (const prestamo of prestamos) {
            const paginaActual = Math.max(1, Number(prestamo.pagina_lectura_actual) || 1);
            const totalPaginas = Math.max(1, Number(prestamo.paginas_lectura_totales) || 1);
            const porcentaje = totalPaginas > 1
                ? Math.min(100, Math.max(1, Math.round((paginaActual / totalPaginas) * 100)))
                : 0;
            const textoProgreso = totalPaginas > 1
                ? `Pagina ${paginaActual} de ${totalPaginas} (${porcentaje}% completado)`
                : `Pagina guardada: ${paginaActual}`;

            const elemento = document.createElement("li");
            elemento.innerHTML = `
                <div class="continuar-lectura-item">
                    ${bloquePortada(prestamo)}
                    <div class="continuar-lectura-texto">
                        <strong>${escaparHtml(prestamo.titulo)} - ${escaparHtml(prestamo.autor)}</strong>
                        <p class="pequeno">Dueno: ${escaparHtml(prestamo.nombre_dueno || "No disponible")}</p>
                        <p class="pequeno">${escaparHtml(textoProgreso)}</p>
                    </div>
                    <div class="continuar-lectura-accion">
                        <button type="button" data-id-prestamo-continuar="${prestamo.id}">Continuar</button>
                    </div>
                </div>
            `;
            listaContinuarLectura.appendChild(elemento);
        }

        for (const lecturaLibre of lecturasLibres) {
            const paginaActual = Math.max(1, Number(lecturaLibre.pagina_actual) || 1);
            const totalPaginas = Math.max(1, Number(lecturaLibre.total_paginas) || 1);
            const porcentaje = totalPaginas > 1
                ? Math.min(100, Math.max(1, Math.round((paginaActual / totalPaginas) * 100)))
                : 0;
            const idioma = String(lecturaLibre.idioma || "es").toLowerCase() === "en" ? "EN" : "ES";
            const textoProgreso = totalPaginas > 1
                ? `Pagina ${paginaActual} de ${totalPaginas} (${porcentaje}% completado)`
                : `Pagina guardada: ${paginaActual}`;

            const elemento = document.createElement("li");
            elemento.innerHTML = `
                <div class="continuar-lectura-item">
                    ${bloquePortada(lecturaLibre)}
                    <div class="continuar-lectura-texto">
                        <strong>${escaparHtml(lecturaLibre.titulo)} - ${escaparHtml(lecturaLibre.autor)}</strong>
                        <p class="pequeno">Catálogo gratuito | Idioma: ${idioma}</p>
                        <p class="pequeno">${escaparHtml(textoProgreso)}</p>
                    </div>
                    <div class="continuar-lectura-accion">
                        <button type="button" data-id-libro-libre-continuar="${escaparHtml(lecturaLibre.id_externo)}">Continuar</button>
                    </div>
                </div>
            `;
            listaContinuarLectura.appendChild(elemento);
        }
    }

    function renderizarMisPrestamos(prestamos) {
        const lista = document.getElementById("listaMisPrestamos");
        lista.innerHTML = "";
        prestamosPorId.clear();

        if (!prestamos.length) {
            lista.innerHTML = "<li>No tienes prestamos registrados.</li>";
            prestamosLecturaActiva = [];
            renderizarContinuarLectura();
            return;
        }

        prestamosLecturaActiva = [];

        for (const prestamo of prestamos) {
            prestamosPorId.set(Number(prestamo.id), prestamo);
            const activo = prestamo.fecha_devolucion === null;
            const botonDevolver = activo
                ? `<button type="button" data-id-prestamo="${prestamo.id}">Devolver</button>`
                : `<span class="pequeno">Devuelto: ${escaparHtml(prestamo.fecha_devolucion)}</span>`;
            const tieneLectura = Number(prestamo.lectura_publica) === 1;
            const botonLeer = (activo && tieneLectura)
                ? `<button type="button" class="boton-secundario" data-id-prestamo-leer="${prestamo.id}">Empezar a leer</button>`
                : "";
            const estadoLectura = tieneLectura
                ? `<p class="pequeno">Lectura publica: disponible</p>`
                : `<p class="pequeno">Lectura publica: no disponible</p>`;
            const progresoLectura = (activo && tieneLectura)
                ? `<p class="pequeno">Pagina guardada: ${Math.max(1, Number(prestamo.pagina_lectura_actual) || 1)}</p>`
                : "";
            if (activo && tieneLectura) {
                prestamosLecturaActiva.push(prestamo);
            }

            const elemento = document.createElement("li");
            elemento.innerHTML = `
                <div class="libro-item">
                    ${bloquePortada(prestamo)}
                    <div>
                        <strong>${escaparHtml(prestamo.titulo)} - ${escaparHtml(prestamo.autor)}</strong>
                        <p class="pequeno">Genero: ${escaparHtml(prestamo.genero || "General")}</p>
                        <p class="pequeno">Dueno: ${escaparHtml(prestamo.nombre_dueno)}</p>
                        <p class="pequeno">Prestado: ${escaparHtml(prestamo.fecha_prestamo)}</p>
                        ${estadoLectura}
                        ${progresoLectura}
                    </div>
                    <div class="libro-accion libro-accion--prestamo">
                        <button type="button" class="boton-secundario" data-id-prestamo-detalle="${prestamo.id}">Ver ficha</button>
                        ${botonLeer}
                        ${botonDevolver}
                    </div>
                </div>
            `;
            lista.appendChild(elemento);
        }

        renderizarContinuarLectura();
    }

    async function cargarLibrosDisponibles(termino = "", genero = "") {
        const parametros = new URLSearchParams();
        if (termino) parametros.set("buscar", termino);
        if (genero) parametros.set("genero", genero);
        const consulta = parametros.toString() ? `?${parametros.toString()}` : "";
        estadoResultadosCatalogoUsuarios.textContent = "Cargando resultados...";
        const datos = await window.fabularia.llamarApi(`/libros${consulta}`);
        renderizarLibrosDisponibles(datos.libros || []);
    }

    async function cargarLibrosCatalogoLibre(texto = "", opciones = {}) {
        const reiniciarPaginacion = Boolean(opciones.reiniciarPaginacion);
        const forzarRecarga = Boolean(opciones.forzarRecarga);
        if (reiniciarPaginacion) {
            paginaLibresEsActual = 1;
            paginaLibresEnActual = 1;
        }

        if (typeof texto === "string") {
            textoBusquedaLibresActual = texto.trim();
        }

        const parametros = new URLSearchParams();
        if (textoBusquedaLibresActual !== "") {
            parametros.set("texto", textoBusquedaLibresActual);
        }
        parametros.set("pagina_es", String(paginaLibresEsActual));
        parametros.set("pagina_en", String(paginaLibresEnActual));
        const consulta = parametros.toString() ? `?${parametros.toString()}` : "";
        const claveCache = claveCacheCatalogoLibre(
            textoBusquedaLibresActual,
            paginaLibresEsActual,
            paginaLibresEnActual
        );

        if (!forzarRecarga && cacheCatalogoLibre.has(claveCache)) {
            renderizarLibrosLibres(cacheCatalogoLibre.get(claveCache));
            catalogoLibreInicializado = true;
            return;
        }

        iniciarAnimacionCargaCatalogoLibres();
        try {
            const datos = await window.fabularia.llamarApi(`/catalogo/libre${consulta}`);
            cacheCatalogoLibre.set(claveCache, datos);
            renderizarLibrosLibres(datos);
            catalogoLibreInicializado = true;
        } finally {
            detenerAnimacionCargaCatalogoLibres();
        }
    }

    async function cambiarPaginaCatalogoLibre(idioma, desplazamiento) {
        const delta = Number(desplazamiento) || 0;
        if (delta === 0) {
            return;
        }

        const paginaAnteriorEs = paginaLibresEsActual;
        const paginaAnteriorEn = paginaLibresEnActual;

        if (idioma === "es") {
            paginaLibresEsActual = Math.max(1, paginaLibresEsActual + delta);
        } else if (idioma === "en") {
            paginaLibresEnActual = Math.max(1, paginaLibresEnActual + delta);
        } else {
            return;
        }

        try {
            await cargarLibrosCatalogoLibre(textoBusquedaLibresActual);
        } catch (error) {
            paginaLibresEsActual = paginaAnteriorEs;
            paginaLibresEnActual = paginaAnteriorEn;
            throw error;
        }
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
        await Promise.all([
            cargarLibrosDisponibles(),
            cargarMisLibros(),
            cargarMisPrestamos()
        ]);
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

    botonesVistaCatalogo.forEach((boton) => {
        boton.addEventListener("click", () => {
            activarVistaCatalogo(boton.dataset.catalogoVista);
        });
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

    document.getElementById("formularioBusquedaUsuarios").addEventListener("submit", async (evento) => {
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

    document.getElementById("formularioBusquedaLibres").addEventListener("submit", async (evento) => {
        evento.preventDefault();
        limpiarMensaje();
        const texto = evento.currentTarget.texto.value.trim();

        try {
            await cargarLibrosCatalogoLibre(texto, { reiniciarPaginacion: true });
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    botonLibresEsAnterior.addEventListener("click", async () => {
        if (paginaLibresEsActual <= 1) {
            return;
        }

        limpiarMensaje();
        try {
            await cambiarPaginaCatalogoLibre("es", -1);
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    botonLibresEsSiguiente.addEventListener("click", async () => {
        if (!haySiguienteLibresEs) {
            return;
        }

        limpiarMensaje();
        try {
            await cambiarPaginaCatalogoLibre("es", 1);
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    botonLibresEnAnterior.addEventListener("click", async () => {
        if (paginaLibresEnActual <= 1) {
            return;
        }

        limpiarMensaje();
        try {
            await cambiarPaginaCatalogoLibre("en", -1);
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    botonLibresEnSiguiente.addEventListener("click", async () => {
        if (!haySiguienteLibresEn) {
            return;
        }

        limpiarMensaje();
        try {
            await cambiarPaginaCatalogoLibre("en", 1);
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

    async function manejarClickCatalogoLibre(evento) {
        const botonDetalle = evento.target.closest("button[data-id-libro-libre-detalle]");
        if (botonDetalle) {
            const idExterno = String(botonDetalle.dataset.idLibroLibreDetalle || "");
            abrirDetalleLibroLibre(idExterno);
            return;
        }

        const botonLeer = evento.target.closest("button[data-id-libro-libre-leer]");
        if (!botonLeer) {
            return;
        }

        const idExterno = String(botonLeer.dataset.idLibroLibreLeer || "");
        const libro = librosCatalogoLibrePorId.get(idExterno);
        if (!libro) {
            mostrarMensaje("No se encontro el libro gratuito seleccionado.", "error");
            return;
        }

        limpiarMensaje();
        try {
            await abrirLectorLibroLibre(libro);
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    }

    listaLibrosLibresEs.addEventListener("click", (evento) => {
        void manejarClickCatalogoLibre(evento);
    });

    listaLibrosLibresEn.addEventListener("click", (evento) => {
        void manejarClickCatalogoLibre(evento);
    });

    cerrarDetalleLibro.addEventListener("click", () => {
        cerrarFichaLibro();
    });

    modalDetalleLibro.addEventListener("click", (evento) => {
        if (evento.target === modalDetalleLibro) {
            cerrarFichaLibro();
        }
    });

    cerrarLectorLibro.addEventListener("click", () => {
        cerrarLector();
    });

    modalLectorLibro.addEventListener("click", (evento) => {
        if (evento.target === modalLectorLibro) {
            cerrarLector();
        }
    });

    botonPaginaAnterior.addEventListener("click", async () => {
        limpiarMensaje();
        try {
            await cargarPaginaLector(paginaLectorActual - 1);
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    botonPaginaSiguiente.addEventListener("click", async () => {
        limpiarMensaje();
        try {
            await cargarPaginaLector(paginaLectorActual + 1);
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    botonGuardarProgresoLectura.addEventListener("click", async () => {
        limpiarMensaje();
        if (modoLectorActual === "libre") {
            if (!referenciaLecturaLibreActual || !referenciaLecturaLibreActual.id_externo) {
                return;
            }

            guardarProgresoLibreGuardado(
                referenciaLecturaLibreActual.id_externo,
                paginaLectorActual,
                totalPaginasLector,
                referenciaLecturaLibreActual
            );
            mostrarMensaje("Progreso del libro gratuito guardado.", "ok", 2200);
            return;
        }

        if (idPrestamoLectorActual <= 0) {
            return;
        }

        try {
            await window.fabularia.llamarApi("/prestamos/lectura/progreso", "POST", {
                id_prestamo: idPrestamoLectorActual,
                pagina_actual: paginaLectorActual,
                total_paginas: totalPaginasLector
            });
            mostrarMensaje("Progreso de lectura guardado.", "ok", 2200);
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    });

    for (const eventoBloqueado of ["copy", "cut", "contextmenu", "selectstart", "dragstart"]) {
        modalLectorLibro.addEventListener(eventoBloqueado, (evento) => {
            if (!modalLectorLibro.hidden) {
                evento.preventDefault();
            }
        });
    }

    document.addEventListener("keydown", (evento) => {
        if (evento.key === "Escape" && !modalLectorLibro.hidden) {
            cerrarLector();
            return;
        }

        if (evento.key === "Escape" && !modalDetalleLibro.hidden) {
            cerrarFichaLibro();
            return;
        }

        if (modalLectorLibro.hidden) {
            return;
        }

        if (evento.key === "ArrowRight") {
            evento.preventDefault();
            void cargarPaginaLector(paginaLectorActual + 1).catch((error) => mostrarMensaje(error.message, "error"));
            return;
        }

        if (evento.key === "ArrowLeft") {
            evento.preventDefault();
            void cargarPaginaLector(paginaLectorActual - 1).catch((error) => mostrarMensaje(error.message, "error"));
            return;
        }

        if (evento.key === "PrintScreen") {
            evento.preventDefault();
            mostrarMensaje("Capturas de pantalla deshabilitadas en este modo de lectura.", "error", 2500);
            return;
        }

        if ((evento.ctrlKey || evento.metaKey) && ["c", "x", "s", "p", "a", "u"].includes(evento.key.toLowerCase())) {
            evento.preventDefault();
            mostrarMensaje("Accion bloqueada en modo lectura protegido.", "error", 2200);
        }
    });

    botonSolicitarDetalle.addEventListener("click", async () => {
        const modo = botonSolicitarDetalle.dataset.modo || "catalogo";
        const idLibro = Number(botonSolicitarDetalle.dataset.idLibro || "0");
        const idPrestamo = Number(botonSolicitarDetalle.dataset.idPrestamo || "0");
        const idExterno = String(botonSolicitarDetalle.dataset.idExterno || "");

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

            if (modo === "catalogo-libre") {
                const libroLibre = librosCatalogoLibrePorId.get(idExterno);
                if (!libroLibre) {
                    throw new Error("No se encontro el libro gratuito.");
                }
                cerrarFichaLibro();
                await abrirLectorLibroLibre(libroLibre);
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

        const botonLeer = evento.target.closest("button[data-id-prestamo-leer]");
        if (botonLeer) {
            limpiarMensaje();
            try {
                await abrirLectorPrestamo(Number(botonLeer.dataset.idPrestamoLeer));
            } catch (error) {
                mostrarMensaje(error.message, "error");
            }
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

    listaContinuarLectura.addEventListener("click", async (evento) => {
        const botonLibre = evento.target.closest("button[data-id-libro-libre-continuar]");
        if (botonLibre) {
            const idExterno = String(botonLibre.dataset.idLibroLibreContinuar || "");
            const indice = cargarIndiceLecturasLibres();
            const lecturaLibre = indice[idExterno] || null;
            if (!lecturaLibre) {
                mostrarMensaje("No se encontro el libro gratuito para continuar.", "error");
                return;
            }

            limpiarMensaje();
            try {
                await abrirLectorLibroLibre(lecturaLibre);
            } catch (error) {
                mostrarMensaje(error.message, "error");
            }
            return;
        }

        const boton = evento.target.closest("button[data-id-prestamo-continuar]");
        if (!boton) {
            return;
        }

        limpiarMensaje();
        try {
            await abrirLectorPrestamo(Number(boton.dataset.idPrestamoContinuar));
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
            activarVistaCatalogo("usuarios");
            activarTab("catalogo");
        } catch (error) {
            mostrarMensaje(error.message, "error");
        }
    })();
</script>
</body>
</html>

