# Fabularia - Intercambio de Libros

Aplicacion en PHP con API REST y formularios para gestionar:

- Registro e inicio de sesion de usuarios (con nombre, apellidos y telefono opcional).
- Publicacion de libros por parte de cada usuario.
- Busqueda de libros disponibles para intercambio con filtro por genero.
- Solicitud y devolucion de prestamos.
- Notificacion a n8n cuando se crea un prestamo (para automatizar Telegram).

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
N8N_WEBHOOK_PRESTAMO=https://n8n.example/webhook-test/REDACTED
TELEGRAM_BOT_URL_BASE=https://t.me/Fabularia_bot?start=
TELEGRAM_VINCULACION_TOKEN=cambia_este_token_compartido_con_n8n
```

4. Crea la base de datos y aplica el esquema:

```sql
SOURCE database/schema.sql;
```

Si ya tenias tablas creadas antes de este cambio, aplica tambien:

```sql
SOURCE database/migracion_apellidos_genero.sql;
SOURCE database/migracion_telefono_usuarios.sql;
SOURCE database/migracion_telegram_usuarios.sql;
```

5. Sirve la carpeta `public/` como raiz web o accede a:

```text
http://localhost/Fabularia/public/
```

## API REST (resumen)

- `POST /api/usuarios/registro`
- `POST /api/usuarios/login`
- `POST /api/usuarios/logout`
- `GET /api/usuarios/yo`
- `POST /api/telegram/vincular`
- `POST /api/libros`
- `GET /api/libros?buscar=texto&genero=Novela`
- `GET /api/libros/mios`
- `POST /api/prestamos`
- `GET /api/prestamos/mios`
- `POST /api/prestamos/devolver`

Cuando se ejecuta `POST /api/prestamos`, la aplicacion envia un webhook a n8n con:

- libro: `id`, `titulo`
- usuario_dueno: `id`, `nombre`, `email`, `telefono`, `telegram_chat_id`, `telegram_usuario`
- usuario_receptor: `id`, `nombre`, `email`, `telefono`, `telegram_chat_id`, `telegram_usuario`

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
- `src/`: controladores, repositorios, enrutador y utilidades HTTP.
- `public/index.php`: front controller de la API y de la vista principal.
- `public/vista_inicio.php`: formularios para consumir la API.
