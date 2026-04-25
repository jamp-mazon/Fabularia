CREATE TABLE IF NOT EXISTS usuarios_restablecimiento_contrasena (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    fecha_usado DATETIME NULL,
    ip_solicitud VARCHAR(45) NULL,
    CONSTRAINT fk_usuarios_restablecimiento_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX ux_usuarios_restablecimiento_token_hash
    ON usuarios_restablecimiento_contrasena(token_hash);
CREATE INDEX idx_usuarios_restablecimiento_usuario
    ON usuarios_restablecimiento_contrasena(id_usuario);
CREATE INDEX idx_usuarios_restablecimiento_expiracion
    ON usuarios_restablecimiento_contrasena(fecha_expiracion);

