# Fabularia - Intercambio de Libros

Fabularia es una aplicacion web en PHP orientada a intercambio de libros entre usuarios.
Incluye API REST, autenticacion, prestamos con reglas de negocio, integracion con Telegram/n8n,
lectura en plataforma (catalogo libre y archivos EPUB/PDF subidos por usuarios), y panel responsive.

## Stack tecnico

- PHP 8.1+
- MySQL/MariaDB
- Composer
- Frontend server-rendered (HTML/CSS/JS sin framework)

Dependencias Composer:

- `vlucas/phpdotenv`: variables de entorno
- `monolog/monolog`: logging de aplicacion
- `phpmailer/phpmailer`: envio SMTP (recuperacion de contrasena)
- `smalot/pdfparser`: extraccion de texto PDF
- `nelexa/zip`: lectura EPUB sin depender de `ZipArchive`

## Funcionalidades principales

- Registro/login/logout de usuarios con nombre, apellidos, email y telefono opcional.
- Cambio de contrasena y eliminacion de cuenta.
- Recuperacion de contrasena por email (token temporal).
- Vinculacion Telegram (bot + webhook n8n).
- Publicacion de libros con portada URL y archivo opcional EPUB/PDF.
- Catalogo de libros de usuarios (intercambio 1:1).
- Catalogo de libros gratuitos (ES/EN) con lectura y progreso.
- Solicitud y devolucion de prestamos.
- Fecha limite de devolucion (maximo 14 dias desde hoy).
- Lector con pagina guardada, barra de progreso y seguimiento en "Mis libros".
- Acciones de seguimiento: continuar lectura, dejar de seguir, devolver y liberar libro.

## Reglas de negocio clave

- Un usuario no puede pedirse prestado su propio libro.
- Para pedir un libro de otro usuario debe tener al menos un libro propio disponible.
- Si un libro esta prestado, no aparece como disponible para nuevos prestamos.
- Al devolver el prestamo, el libro vuelve a estar disponible en catalogo.
- La fecha limite del prestamo no puede superar 14 dias desde la fecha actual.

## Instalacion

1. Instalar dependencias:

```bash
composer install
```

2. Crear entorno:

```bash
cp .env.example .env
```

3. Configurar `.env` (sin subir secretos a Git):

```dotenv
APP_TIMEZONE=Europe/Madrid
APP_URL_BASE=http://localhost/Fabularia/public

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=fabularia
DB_USER=root
DB_PASS=

N8N_WEBHOOK_PRESTAMO=
TELEGRAM_BOT_URL_BASE=
TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=
TELEGRAM_VINCULACION_TOKEN=
GOOGLE_BOOKS_API_KEY=

MAIL_FROM_EMAIL=no-reply@fabularia.local
MAIL_FROM_NAME=Fabularia
MAIL_DRIVER=smtp
SMTP_HOST=
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
SMTP_ENCRYPTION=tls
SMTP_AUTH=true
SMTP_TIMEOUT=20

PASSWORD_RESET_TTL_MINUTES=30
LECTURA_CACHE_DIR=/www/proyectos/fabularia/storage/lecturas
```

## Cache del catalogo libre en VPS

El catalogo gratuito consulta Gutendex. Para que el login no dependa de esa API externa, la aplicacion no precarga el catalogo al iniciar sesion.

En VPS puedes precalentar la cache con:

```bash
php /www/proyectos/fabularia/scripts/precalentar_catalogo_libre.php 3 10
```

Parametros:

- `3`: paginas por idioma a precalentar.
- `10`: libros por pagina.

Cron recomendado cada 15 minutos:

```cron
*/15 * * * * /usr/bin/php /www/proyectos/fabularia/scripts/precalentar_catalogo_libre.php 3 10 >/dev/null 2>&1
```

Si Gutendex falla o responde lento, la aplicacion intenta mostrar los libros ya guardados en `LECTURA_CACHE_DIR`.
Comprueba que esa carpeta exista y sea escribible por PHP.

## Diagnostico de correo en VPS

La recuperacion de contrasena usa `MAIL_DRIVER=smtp`. En hosting/VPS no conviene depender de `mail()` porque puede fallar sin dar mucho detalle o acabar bloqueado por el proveedor.

Configuracion minima esperada en `.env`:

```dotenv
MAIL_DRIVER=smtp
MAIL_FROM_EMAIL=correo_verificado_en_el_proveedor
MAIL_FROM_NAME=Fabularia
SMTP_HOST=smtp-relay.brevo.com
SMTP_PORT=587
SMTP_USER=usuario_smtp
SMTP_PASS=contrasena_smtp
SMTP_ENCRYPTION=tls
SMTP_AUTH=true
SMTP_TIMEOUT=20
```

Para probar el envio sin depender de la base de datos:

```bash
php /www/proyectos/fabularia/scripts/probar_correo.php destino@ejemplo.com
```

Si falla, revisar `logs/app.log`. El log muestra host, puerto, cifrado y remitente, pero no muestra la contrasena SMTP.

Notas:

- `MAIL_FROM_EMAIL` debe ser un remitente o dominio verificado en el proveedor SMTP.
- Si el email no existe en la base de datos, la aplicacion devuelve el mismo mensaje generico por seguridad y no envia correo.
- Si el proveedor acepta el envio pero no llega, revisar spam y el panel de actividad del proveedor SMTP.

## Base de datos

Aplicar esquema completo:

```sql
SOURCE database/schema.sql;
```

Si vienes de una version anterior, aplica tambien migraciones pendientes:

```sql
SOURCE database/migracion_apellidos_genero.sql;
SOURCE database/migracion_telefono_usuarios.sql;
SOURCE database/migracion_telegram_usuarios.sql;
SOURCE database/migracion_portada_libros.sql;
SOURCE database/migracion_lectura_publica_prestamos.sql;
SOURCE database/migracion_archivos_libros.sql;
SOURCE database/migracion_restablecimiento_contrasena.sql;
SOURCE database/migracion_fecha_limite_prestamos.sql;
SOURCE database/migracion_normalizar_telefonos.sql;
```

## Ejecucion local

Servir `public/` como raiz web o abrir:

```text
http://localhost/Fabularia/public/
```

Vistas:

- `GET /login`
- `GET /registro`
- `GET /recuperar-contrasena`
- `GET /restablecer-contrasena?token=...`
- `GET /app`

## API REST (resumen)

Usuarios:

- `POST /api/usuarios/registro`
- `POST /api/usuarios/login`
- `POST /api/usuarios/logout`
- `GET /api/usuarios/yo`
- `POST /api/usuarios/telefono`
- `POST /api/usuarios/cambiar-contrasena`
- `POST /api/usuarios/solicitar-restablecimiento`
- `POST /api/usuarios/restablecer-contrasena`
- `POST /api/usuarios/telegram/desvincular`
- `DELETE /api/usuarios/cuenta`

Catalogo:

- `GET /api/catalogo/sugerencias?texto=...`
- `GET /api/catalogo/libre?texto=...&genero=...&pagina_es=1&pagina_en=1`
- `GET /api/catalogo/libre/lectura?id_externo=...&pagina=...`

Telegram:

- `POST /api/telegram/vincular`

Libros:

- `POST /api/libros`
- `GET /api/libros?buscar=...&genero=...`
- `GET /api/libros/mios`
- `DELETE /api/libros` (JSON: `id_libro`)

Prestamos:

- `POST /api/prestamos` (acepta `fecha_limite_devolucion` en formato `Y-m-d`)
- `GET /api/prestamos/mios`
- `GET /api/prestamos/lectura?id_prestamo=...&pagina=...`
- `POST /api/prestamos/lectura/progreso`
- `POST /api/prestamos/devolver`

## Integracion Telegram

- El usuario abre el bot con `TELEGRAM_BOT_URL_BASE + USUARIO_ID`.
- Telegram llama directamente a `/api/telegram/webhook`.
- Fabularia lee `/start USUARIO_ID`, `message.chat.id`, `message.from.username` y guarda la vinculacion en `usuarios`.
- Al crear un prestamo, Fabularia envia webhook a n8n con datos de libro, usuario propietario, usuario receptor y fecha limite.
- n8n se usa para enviar mensajes de Telegram cuando hay prestamos, no para vincular usuarios.

Webhook recomendado para Telegram:

```text
https://TU_DOMINIO/api/telegram/webhook
```

Configura en `.env`:

```dotenv
TELEGRAM_BOT_TOKEN=token_real_del_bot
TELEGRAM_WEBHOOK_SECRET=secreto_sin_dos_puntos_ni_espacios
TELEGRAM_BOT_URL_BASE=https://t.me/Fabularia_bot?start=
```

No uses el token real del bot como `TELEGRAM_WEBHOOK_SECRET`. El secret debe usar solo letras, numeros, guion y guion bajo.

Para configurar el webhook desde el VPS:

```bash
php /www/proyectos/fabularia/scripts/configurar_webhook_telegram.php
```

El script usa `APP_URL_BASE/api/telegram/webhook`. Si quieres pasar la URL manualmente:

```bash
php /www/proyectos/fabularia/scripts/configurar_webhook_telegram.php https://TU_DOMINIO/api/telegram/webhook
```

Telegram enviara `TELEGRAM_WEBHOOK_SECRET` como cabecera `X-Telegram-Bot-Api-Secret-Token`.

Payload esperado desde Telegram:

```json
{
  "message": {
    "text": "/start 1",
    "chat": {
      "id": 123456789
    },
    "from": {
      "username": "usuario_telegram",
      "first_name": "Usuario"
    }
  }
}
```

Tambien se mantiene `/api/telegram/vincular` para pruebas manuales o compatibilidad, pero la vinculacion real debe apuntar a `/api/telegram/webhook`.
`TELEGRAM_BOT_TOKEN` y `TELEGRAM_WEBHOOK_SECRET` no deben publicarse ni subirse a Git.

## Notas de seguridad y buenas practicas

- No incluir tokens, webhooks ni claves SMTP en `README.md` ni en commits.
- Mantener `.env` fuera de Git.
- Rotar credenciales si alguna vez se expusieron.

## Estructura principal

- `config/`: bootstrap y configuracion inicial.
- `database/`: esquema y migraciones SQL.
- `public/`: front controller, vistas y assets.
- `src/Controladores/`: endpoints y reglas de negocio.
- `src/Repositorios/`: acceso a base de datos.
- `src/Servicios/`: catalogo externo, lectura, correo y webhooks.
- `src/Http/`: router y utilidades HTTP.
- `logs/`: registro de eventos (`app.log`).
- `storage/`: archivos subidos y cache de lectura.

## Estado actual

La app esta preparada para entrega funcional de la practica:

- flujo completo de usuarios,
- intercambio entre usuarios,
- lectura y progreso,
- recuperacion de contrasena,
- integraciones automatizables con n8n/Telegram,
- y UI responsive con mejoras de usabilidad.
