ALTER TABLE usuarios
    ADD COLUMN telegram_chat_id VARCHAR(40) NULL AFTER telefono,
    ADD COLUMN telegram_usuario VARCHAR(120) NULL AFTER telegram_chat_id;

CREATE UNIQUE INDEX ux_usuarios_telegram_chat_id ON usuarios(telegram_chat_id);
