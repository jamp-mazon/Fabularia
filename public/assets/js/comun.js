window.fabularia = (function () {
    let urlBaseApi = "";
    let urlBaseTelegram = "https://t.me/Fabularia_bot?start=";

    function configurar(opciones) {
        urlBaseApi = opciones.urlBaseApi || "";
        urlBaseTelegram = opciones.urlBaseTelegram || urlBaseTelegram;
    }

    async function llamarApi(ruta, metodo = "GET", datos = null) {
        const opciones = { method: metodo, headers: {} };
        if (datos !== null) {
            opciones.headers["Content-Type"] = "application/json";
            opciones.body = JSON.stringify(datos);
        }

        const respuesta = await fetch(`${urlBaseApi}${ruta}`, opciones);
        const cuerpo = await respuesta.json().catch(() => ({}));

        if (!respuesta.ok) {
            throw new Error(cuerpo.error || "Se produjo un error en la petición.");
        }

        return cuerpo;
    }

    function enlaceTelegram(idUsuario) {
        return `${urlBaseTelegram}${idUsuario}`;
    }

    return {
        configurar,
        llamarApi,
        enlaceTelegram
    };
})();
