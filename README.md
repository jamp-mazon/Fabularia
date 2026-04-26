# Fabularia - Intercambio de Libros

Aplicacion en PHP con API REST y formularios para gestionar:

- Registro e inicio de sesion de usuarios (con nombre, apellidos y telefono opcional).
- Publicacion de libros por parte de cada usuario (con portada opcional por URL).
- Autocompletado de libros desde catalogo global (Google Books en español).
- Busqueda de libros disponibles para intercambio con filtro por genero.
- Solicitud y devolucion de prestamos.
- Notificacion a n8n cuando se crea un prestamo (para automatizar Telegram).
- Lector para prestamos con pagina guardada y barra de progreso (solo si hay fuente publica).

El proyecto utiliza Composer con:

- `vlucas/phpdotenv` para variables de entorno.
- `monolog/monolog` para registro de eventos en `logs/app.log`.

## Requisitos

- PHP 8.1 o superior
- MySQL/MariaDB
- Composer
- Apache con `mod_rewrite` (recomendado)

## Instalacion

1. Instala dependencias:

```bash
composer install
```

2. Crea el archivo de entorno:

```bash
cp .env.example .env
```

3. Ajusta los datos de conexion en `.env`.
   Configura tambien:

```dotenv
N8N_WEBHOOK_PRESTAMO=
TELEGRAM_BOT_URL_BASE=
TELEGRAM_VINCULACION_TOKEN=
GOOGLE_BOOKS_API_KEY=
```

`GOOGLE_BOOKS_API_KEY` es opcional, pero recomendado para limites de uso mas altos del catalogo externo.

4. Crea la base de datos y aplica el esquema:

```sql
SOURCE database/schema.sql;
```

Si ya tenias tablas creadas antes de este cambio, aplica tambien:

```sql
SOURCE database/migracion_apellidos_genero.sql;
SOURCE database/migracion_telefono_usuarios.sql;
SOURCE database/migracion_telegram_usuarios.sql;
SOURCE database/migracion_portada_libros.sql;
SOURCE database/migracion_lectura_publica_prestamos.sql;
```

5. Sirve la carpeta `public/` como raiz web o accede a:

```text
http://localhost/Fabularia/public/
```

Rutas de interfaz:

- `GET /login`
- `GET /registro`
- `GET /app`

## API REST (resumen)

- `POST /api/usuarios/registro`
- `POST /api/usuarios/login`
- `POST /api/usuarios/logout`
- `GET /api/usuarios/yo`
- `POST /api/usuarios/cambiar-contrasena`
- `POST /api/usuarios/telegram/desvincular`
- `DELETE /api/usuarios/cuenta`
- `GET /api/catalogo/sugerencias?texto=harry`
- `GET /api/catalogo/libre?texto=quijote` (catalogo gratuito ES/EN)
- `GET /api/catalogo/libre/lectura?id_externo=123&pagina=1`
- `POST /api/telegram/vincular`
- `POST /api/libros`
- `GET /api/libros?buscar=texto&genero=Novela`
- `GET /api/libros/mios`
- `DELETE /api/libros` (JSON: `id_libro`)
- `POST /api/prestamos`
- `GET /api/prestamos/mios`
- `GET /api/prestamos/lectura?id_prestamo=10&pagina=2`
- `POST /api/prestamos/lectura/progreso`
- `POST /api/prestamos/devolver`

Reglas de negocio de catalogo:

- **Catalogo gratuito**: lectura directa sin regla 1:1 (idiomas permitidos ES/EN con aviso visual).
- **Catalogo de usuarios**: para solicitar prestamo se exige tener al menos un libro propio disponible para intercambio.

El endpoint de catalogo global devuelve sugerencias con:

- `titulo`
- `autor`
- `genero`
- `descripcion`
- `portada_url`

Cuando se ejecuta `POST /api/prestamos`, la aplicacion envia un webhook a n8n con:

- libro: `id`, `titulo`, `portada_url`
- usuario_dueno: `id`, `nombre`, `email`, `telefono`, `telegram_chat_id`, `telegram_usuario`
- usuario_receptor: `id`, `nombre`, `email`, `telefono`, `telegram_chat_id`, `telegram_usuario`

La interfaz `/app` incluye pestaña de **Ajustes** para:

- cambiar contrasena,
- desvincular Telegram,
- cerrar sesion,
- eliminar cuenta (requiere confirmacion y contrasena).

Al eliminar una cuenta se limpian primero los prestamos vinculados a ese usuario
para respetar las claves foraneas actuales.

En `Mis libros`, el propietario puede eliminar un libro solo si no tiene
prestamo activo. Si esta prestado, la API responde error de conflicto (409).

## Vinculacion Telegram

Cuando un usuario abre `https://t.me/Fabularia_bot?start=`, tu workflow de n8n debe:

1. Leer `chat_id` y `username` del update de Telegram.
2. Extraer `USUARIO_ID` del parametro `start`.
3. Llamar a `POST /api/telegram/vincular` con JSON:

```json
{
  "usuario_id": 4,
  "telegram_chat_id": "123456789",
  "telegram_usuario": "mi_usuario_telegram",
  "token_vinculacion": "mismo_valor_que_TELEGRAM_VINCULACION_TOKEN"
}
```

Tambien puedes enviar el token en cabecera HTTP `X-Vinculacion-Token`.

## Estructura principal

- `config/bootstrap.php`: carga de entorno, logger y conexion BD.
- `database/schema.sql`: tablas `usuarios`, `libros`, `prestamos`.
- `database/migracion_apellidos_genero.sql`: alteraciones para anadir `apellidos` y `genero` a esquemas existentes.
- `database/migracion_telefono_usuarios.sql`: alteracion para anadir `telefono` en usuarios.
- `database/migracion_telegram_usuarios.sql`: alteracion para anadir `telegram_chat_id` y `telegram_usuario`.
- `database/migracion_portada_libros.sql`: alteracion para anadir `portada_url` en libros.
- `database/migracion_lectura_publica_prestamos.sql`: columnas para lectura publica y progreso por prestamo.
- `src/`: controladores, repositorios, enrutador y utilidades HTTP.
- `public/index.php`: front controller de la API y de la vista principal.
- `public/vistas/login.php`: pantalla de acceso.
- `public/vistas/registro.php`: alta de usuario.
- `public/vistas/aplicacion.php`: panel principal de la app.
- `public/assets/`: estilos y JS compartidos de interfaz.
